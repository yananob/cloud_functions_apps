#!/bin/bash
set -eu

./vendor/bin/phpstan analyze -c phpstan.neon

./vendor/bin/phpunit --colors=auto --display-notices --display-warnings --display-errors tests/
# ./vendor/bin/phpunit --colors=auto --display-notices --display-warnings --display-errors tests/CrawlerTest.php
# ./vendor/bin/phpunit --colors=auto --display-notices --display-warnings --display-errors tests/AccountsTest.php
# ./vendor/bin/phpunit --colors=auto --display-notices --display-warnings --display-errors tests/OmlBookTest.php
# ./vendor/bin/phpunit --colors=auto --display-notices --display-warnings --display-errors tests/OmlTest.php
