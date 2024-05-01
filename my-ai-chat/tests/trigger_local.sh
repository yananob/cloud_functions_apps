#!/bin/bash

set -eu

curl -X POST -H "Content-Type: application/json" -d '{"message": "Do you like orange?"}' "http://localhost:8080"
