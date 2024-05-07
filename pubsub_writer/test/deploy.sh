#!/bin/bash

set -eu

echo "deploying pubsub function"
gcloud functions deploy pubsub-test-sub \
--gen2 \
--runtime=python311 \
--region=us-west1 \
--source=. \
--entry-point=sub_main \
--trigger-topic=gas-test

echo "deploying http function"
gcloud functions deploy pubsub-test-http \
--gen2 \
--runtime=python311 \
--region=us-west1 \
--source=. \
--entry-point=http_main \
--trigger-http
