#!/bin/bash
set -e

export FUNCTION_TARGET=update
export FUNCTION_SIGNATURE_TYPE=cloudevent
php -S localhost:8081 vendor/bin/router.php
