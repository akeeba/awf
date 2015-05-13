#!/bin/sh

LC_ALL=C ../vendor/bin/phpunit -c ../phpunit.xml ../tests/ ${@}