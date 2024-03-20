#!/bin/bash
set -e

./vendor/bin/phpstan analyze -c phpstan.neon

./vendor/bin/phpunit --colors=auto --display-notices --display-warnings --display-errors tests/
# ./vendor/bin/phpunit --colors=auto --display-notices --display-warnings --testdox tests/CrawlerTest.php
# ./vendor/bin/phpunit --colors=auto --display-notices --display-warnings --testdox tests/AccountsTest.php
# ./vendor/bin/phpunit --colors=auto --display-notices --display-warnings --testdox tests/OmlBookTest.php
# ./vendor/bin/phpunit --colors=auto --display-notices --display-warnings --testdox tests/OmlTest.php
