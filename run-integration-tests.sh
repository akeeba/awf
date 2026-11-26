#!/usr/bin/env bash
# =============================================================================
# AWF — database integration test runner
#
# Spins up a throwaway Docker container for each configured database server
# version, runs the Integration test suite against it, then tears it down.
#
# Usage:
#   ./run-integration-tests.sh                 # test every configured image
#   ./run-integration-tests.sh mariadb:11.4    # test one specific image
#   ./run-integration-tests.sh mariadb         # test every image of one family
#   ./run-integration-tests.sh --host=local    # force a run on this machine
#
# Remote host: the whole run happens on the E2E test host over SSH when one is
# configured (--host=HOST, E2E_DOCKER_HOST, or e2e.docker.host in the Phing
# properties files; see "Remote E2E hosts" in ../buildfiles/README.md). When
# the host came from the environment or the properties files and cannot be
# reached, or its Docker daemon is down, the run falls back to this machine
# with a warning. An explicit --host=HOST never falls back. --slot=N is passed
# to the shared remote runner.
#
# Which versions get spun up can be overridden from the shell:
#
#   INTEGRATION_MARIADB_IMAGES="mariadb:11.4" ./run-integration-tests.sh
#
# Requirements: Docker, PHP CLI with the mysqli, pdo_mysql, pgsql and
# pdo_pgsql extensions, wherever the run happens. A test whose extension is
# missing skips itself. A checkout of akeeba/buildfiles next to this one.
#
# Ported from the Akeeba Engine's run-integration-tests.sh.
# =============================================================================

set -euo pipefail

cd "$(dirname "$0")"

REPO_ROOT="$(pwd)"

# ---------------------------------------------------------------------------
# Local or remote host
#
# In remote mode e2e_remote copies the working tree to the E2E host, runs this
# same script there, and exits with its status. Nothing below it runs here.
# ---------------------------------------------------------------------------

E2E_REMOTE_SH="${REPO_ROOT}/../buildfiles/docker/e2e/remote.sh"
# A nested linked worktree has no ../buildfiles of its own: use the one next to the main repository.
[ -f "${E2E_REMOTE_SH}" ] || E2E_REMOTE_SH="$(dirname "$(git -C "${REPO_ROOT}" rev-parse --path-format=absolute --git-common-dir)")/../buildfiles/docker/e2e/remote.sh"

if [ ! -f "${E2E_REMOTE_SH}" ]; then
	echo "buildfiles not found. Check out akeeba/buildfiles next to this repository." >&2
	exit 1
fi

# shellcheck source=/dev/null
. "${E2E_REMOTE_SH}"

# Read by the sourced remote.sh.
# shellcheck disable=SC2034
E2E_HARNESS_DIR="${REPO_ROOT}"
# There is no docker-compose.yml to read the project name from.
# shellcheck disable=SC2034
E2E_PROJECT="awf-integration"
# shellcheck disable=SC2034
E2E_PORTS=(INTEGRATION_DB_HOST_PORT INTEGRATION_PGSQL_HOST_PORT)
# The suite runs vendor/bin/phpunit, and vendor/ is ignored by Git.
# shellcheck disable=SC2034
E2E_INCLUDE=(".:vendor")
# shellcheck disable=SC2034
E2E_KEEP_FLAGS=()

# Fall back to this machine when the configured host is unavailable. The shared
# runner treats an unreachable host as an error, so decide before calling it.
remote_fallback() {
	local arg want=0 host

	[ "${E2E_AGENT:-}" != "1" ] || return 0

	for arg in "$@"; do
		# The value of --slot.
		if [ "${want}" -eq 1 ]; then
			want=0
			continue
		fi

		case "${arg}" in
			--) break ;;
			--host | --host=*) return 0 ;; # Explicitly asked for: never fall back.
			--slot) want=1 ;;
		esac
	done

	e2e_resolve E2E_DOCKER_HOST e2e.docker.host "" "" "${REPO_ROOT}" "$(e2e_main_repo "${REPO_ROOT}")" || exit 1
	host="${E2E_VALUE}"

	[ "${host}" != "local" ] || return 0

	if ssh -o BatchMode=yes -o ConnectTimeout="${E2E_PROBE_TIMEOUT:-10}" "${host}" 'docker ps -q >/dev/null' \
		</dev/null >/dev/null 2>&1; then
		return 0
	fi

	echo "WARNING: the E2E host '${host}' (from ${E2E_SOURCE}) is unreachable, or its Docker daemon is down." >&2
	echo "WARNING: running the integration tests on THIS machine instead." >&2

	export E2E_DOCKER_HOST=local
}

remote_fallback "$@"
e2e_remote "${BASH_SOURCE[0]}" "$@"

# ---------------------------------------------------------------------------
# Configuration
# ---------------------------------------------------------------------------

# Server versions to test, per family. Override from the shell.
INTEGRATION_MYSQL_IMAGES="${INTEGRATION_MYSQL_IMAGES:-mysql:8.0 mysql:8.4}"
INTEGRATION_MARIADB_IMAGES="${INTEGRATION_MARIADB_IMAGES:-mariadb:11.4 mariadb:12.3}"
INTEGRATION_POSTGRES_IMAGES="${INTEGRATION_POSTGRES_IMAGES:-postgres:16 postgres:17}"

# On the E2E host: this run's own ports, over whatever the environment says.
e2e_apply_overrides

# Host ports mapped to the container. Change these if they are already taken.
INTEGRATION_DB_HOST_PORT="${INTEGRATION_DB_HOST_PORT:-13306}"
INTEGRATION_PGSQL_HOST_PORT="${INTEGRATION_PGSQL_HOST_PORT:-15432}"

# Credentials seeded into the container.
DB_PASSWORD="${DB_PASSWORD:-awftest}"
DB_NAME="${DB_NAME:-awftest}"

# How long to wait for a server to accept connections, in seconds.
MAX_WAIT_SECONDS="${MAX_WAIT_SECONDS:-120}"

CONTAINER_NAME="awf_integration_$$"
FAILED=0

if ! docker info >/dev/null 2>&1; then
	echo "Docker is not available. The database integration tests need a running Docker daemon."
	exit 1
fi

# ---------------------------------------------------------------------------
# Resolve which images to test
# ---------------------------------------------------------------------------

ALL_IMAGES=($INTEGRATION_MYSQL_IMAGES $INTEGRATION_MARIADB_IMAGES $INTEGRATION_POSTGRES_IMAGES)

# The first argument which is not an option selects the images.
SELECTION=""

while [ $# -gt 0 ]; do
	case "$1" in
		--host | --slot) shift 2 || shift ;; # read by e2e_remote above
		--host=* | --slot=*) shift ;;
		*)
			[ -n "${SELECTION}" ] || SELECTION="$1"
			shift
			;;
	esac
done

case "${SELECTION}" in
	'')
		IMAGES=("${ALL_IMAGES[@]}")
		;;
	mysql)
		IMAGES=($INTEGRATION_MYSQL_IMAGES)
		;;
	mariadb)
		IMAGES=($INTEGRATION_MARIADB_IMAGES)
		;;
	postgres | postgresql | pgsql)
		IMAGES=($INTEGRATION_POSTGRES_IMAGES)
		;;
	*)
		# A specific image, e.g. postgres:17
		IMAGES=("${SELECTION}")
		;;
esac

if [ "${#IMAGES[@]}" -eq 0 ]; then
	echo "No images configured to test."
	exit 1
fi

# The family decides the port, the seeding variables and the readiness probe.
family_of() {
	case "${1%%:*}" in
		mysql) echo "mysql" ;;
		mariadb) echo "mariadb" ;;
		postgres | postgresql) echo "postgres" ;;
		*) echo "unknown" ;;
	esac
}

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

cleanup_container() {
	docker rm --force "$CONTAINER_NAME" >/dev/null 2>&1 || true
}

# Kill the container even if the run is interrupted. INT and TERM exit, so the
# EXIT trap runs and the loop does not carry on with the next image.
trap cleanup_container EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

# On the E2E host the agent removes whatever carries this run's project label,
# should the run be killed before its own trap can.
CONTAINER_LABELS=()
[ "${E2E_AGENT:-}" != "1" ] || CONTAINER_LABELS=(--label "com.docker.compose.project=${COMPOSE_PROJECT_NAME:?}")

# Poll until the server accepts connections, or give up.
wait_for_db() {
	local family="$1" waited=0 probe

	case "$family" in
		postgres)
			probe=(pg_isready --host=127.0.0.1 --username=postgres --dbname="$DB_NAME")
			;;
		mariadb)
			# MariaDB 11+ ships mariadb-admin; its mysqladmin symlink is deprecated.
			probe=(mariadb-admin --protocol=tcp --host=127.0.0.1 --user=root "--password=${DB_PASSWORD}" ping)
			;;
		*)
			probe=(mysqladmin --protocol=tcp --host=127.0.0.1 --user=root "--password=${DB_PASSWORD}" ping)
			;;
	esac

	echo "  Waiting for the server to accept connections..."

	# The probes go over TCP: the official images start a temporary, socket-only server while they seed the
	# database, and a socket probe would report that one as ready.
	while ! docker exec "$CONTAINER_NAME" "${probe[@]}" >/dev/null 2>&1; do
		waited=$((waited + 2))

		if [ "$waited" -ge "$MAX_WAIT_SECONDS" ]; then
			echo "  Timed out after ${MAX_WAIT_SECONDS} seconds."
			return 1
		fi

		sleep 2
	done

	echo "  The server is ready."
	return 0
}

start_container() {
	local image="$1" family="$2"

	if [ "$family" = "postgres" ]; then
		docker run --detach \
			--name "$CONTAINER_NAME" \
		${CONTAINER_LABELS[@]+"${CONTAINER_LABELS[@]}"} \
			--env "POSTGRES_PASSWORD=${DB_PASSWORD}" \
			--env "POSTGRES_DB=${DB_NAME}" \
			--publish "127.0.0.1:${INTEGRATION_PGSQL_HOST_PORT}:5432" \
			"$image" >/dev/null

		return
	fi

	# Both MYSQL_* and MARIADB_* variables are supplied so the container is
	# seeded regardless of which set the image recognises.
	docker run --detach \
		--name "$CONTAINER_NAME" \
		${CONTAINER_LABELS[@]+"${CONTAINER_LABELS[@]}"} \
		--env "MYSQL_ROOT_PASSWORD=${DB_PASSWORD}" \
		--env "MARIADB_ROOT_PASSWORD=${DB_PASSWORD}" \
		--env "MYSQL_DATABASE=${DB_NAME}" \
		--env "MARIADB_DATABASE=${DB_NAME}" \
		--publish "127.0.0.1:${INTEGRATION_DB_HOST_PORT}:3306" \
		"$image" >/dev/null
}

# Run the Integration suite against the container we just started.
#
# The other family's connection variables are removed from the environment, so a developer who has them exported
# never has those tests run against their own server while we iterate over this family's images. Tests whose
# variables are absent skip themselves.
run_suite() {
	local family="$1"

	if [ "$family" = "postgres" ]; then
		env -u AWF_TEST_MYSQL_DSN -u AWF_TEST_MYSQL_USER -u AWF_TEST_MYSQL_PASS \
			AWF_TEST_PG_DSN="pgsql:host=127.0.0.1;port=${INTEGRATION_PGSQL_HOST_PORT};dbname=${DB_NAME}" \
			AWF_TEST_PG_USER=postgres \
			AWF_TEST_PG_PASS="$DB_PASSWORD" \
			vendor/bin/phpunit --testsuite Integration

		return
	fi

	env -u AWF_TEST_PG_DSN -u AWF_TEST_PG_USER -u AWF_TEST_PG_PASS \
		AWF_TEST_MYSQL_DSN="mysql:host=127.0.0.1;port=${INTEGRATION_DB_HOST_PORT};dbname=${DB_NAME}" \
		AWF_TEST_MYSQL_USER=root \
		AWF_TEST_MYSQL_PASS="$DB_PASSWORD" \
		vendor/bin/phpunit --testsuite Integration
}

run_tests_against() {
	local image="$1"
	local family

	family="$(family_of "$image")"

	echo ""
	echo "=========================================="
	echo "  Image : $image"
	echo "=========================================="

	if [ "$family" = "unknown" ]; then
		echo "  FAILED: cannot tell which database family '$image' belongs to."
		echo "          Expected an image named mysql:*, mariadb:* or postgres:*."

		return 1
	fi

	# Remove any leftover container of the same name.
	cleanup_container

	start_container "$image" "$family"

	if ! wait_for_db "$family"; then
		echo "  FAILED: the server never became ready ($image)"
		cleanup_container

		return 1
	fi

	if ! run_suite "$family"; then
		echo ""
		echo "  FAILED: $image (test failures above)"
		cleanup_container

		return 1
	fi

	echo ""
	echo "  PASSED: $image"

	cleanup_container

	return 0
}

# ---------------------------------------------------------------------------
# Main loop
# ---------------------------------------------------------------------------

for IMAGE in "${IMAGES[@]}"; do
	if ! run_tests_against "$IMAGE"; then
		FAILED=$((FAILED + 1))
	fi
done

# ---------------------------------------------------------------------------
# Summary
# ---------------------------------------------------------------------------

TOTAL="${#IMAGES[@]}"
PASSED=$((TOTAL - FAILED))

echo ""
echo "=========================================="
echo "  Results: $PASSED/$TOTAL image(s) passed"
echo "=========================================="

if [ "$FAILED" -ne 0 ]; then
	exit 1
fi
