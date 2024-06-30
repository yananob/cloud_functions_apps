#!/bin/bash
set -eu

./vendor/bin/phpstan analyze -c phpstan.neon

./vendor/bin/phpunit --colors=auto --display-notices --display-warnings tests/
