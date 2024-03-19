#!/bin/bash
set -e

export GOOGLE_APPLICATION_CREDENTIALS="./src/common/credentials/gcp_serviceaccount.json"

export FUNCTION_TARGET=main
export FUNCTION_SIGNATURE_TYPE=cloudevent
php -S localhost:8080 vendor/bin/router.php
