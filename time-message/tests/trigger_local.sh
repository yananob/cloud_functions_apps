#!/bin/bash
set -e

curl localhost:8080 \
    -H "ce-id: 9999999999" \
    -H "ce-source: //pubsub.googleapis.com/projects/test-pj/topics/time-message" \
    -H "ce-specversion: 1.0" \
    -H "ce-type: com.google.cloud.pubsub.topic.publish" \
    -d '{"foo": "bar"}'
