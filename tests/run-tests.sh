#!/bin/sh

if [ "$1" ]
then
	../vendor/bin/phpunit-randomizer --seed $1 -c ../phpunit.xml ../tests/
else
	../vendor/bin/phpunit-randomizer -c ../phpunit.xml ../tests/
fi