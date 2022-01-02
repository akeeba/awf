#!/bin/sh

##
# @package   awf
# @copyright Copyright (c)2014-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
# @license   GNU GPL version 3 or later
#

LC_ALL=C ../vendor/bin/phpunit -c ../phpunit.xml ../tests/ ${@}