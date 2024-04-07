#!/bin/bash
set -e

./vendor/bin/phpstan analyze -c phpstan.neon

./vendor/bin/phpunit --colors=auto --display-notices --display-warnings --display-errors tests/
# ./vendor/bin/phpunit --colors=auto --display-notices --display-warnings tests/CrawlerTest.php
# ./vendor/bin/phpunit --colors=auto --display-notices --display-warnings tests/AccountsTest.php
# ./vendor/bin/phpunit --colors=auto --display-notices --display-warnings tests/OmlBookTest.php
# ./vendor/bin/phpunit --colors=auto --display-notices --display-warnings tests/OmlTest.php
