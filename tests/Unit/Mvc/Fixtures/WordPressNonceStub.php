<?php
/**
 * A stand-in for WordPress' wp_verify_nonce(), for testing the CMS branch of Controller::csrfProtection().
 *
 * $GLOBALS['awf_test_wp_nonces'] maps a nonce to the one action it is valid for. Every call is recorded in
 * $GLOBALS['awf_test_wp_nonce_calls'] as [nonce, action].
 */

if (!function_exists('wp_verify_nonce'))
{
	function wp_verify_nonce($nonce, $action = -1)
	{
		$GLOBALS['awf_test_wp_nonce_calls'][] = [$nonce, $action];

		$valid = $GLOBALS['awf_test_wp_nonces'] ?? [];

		return (is_string($nonce) && isset($valid[$nonce]) && $valid[$nonce] === $action) ? 1 : false;
	}
}
