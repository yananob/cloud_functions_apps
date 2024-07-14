#!/bin/bash
set -eu

./vendor/bin/phpstan analyze -c phpstan.neon

echo "Running tests $1..."
./vendor/bin/phpunit --colors=auto --display-notices --display-warnings --display-errors tests/$1
