#!/bin/bash
set -eu

curl -X POST \
    -H "context-type: application:json" \
    -d '{"events": ["source": {"type": "group", "groupId": "GROUP_ID"}, "message": {"text": "MESSAGE"}, "replyToken": "REPLY_TOKEN"]}' \
    http://localhost:8080
