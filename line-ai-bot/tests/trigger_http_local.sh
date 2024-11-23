#!/bin/bash
set -eu

curl -X POST \
    -H "context-type: application:json" \
    -d '{"source": "local"}' \
    http://localhost:8080?param=abc
