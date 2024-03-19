#!/bin/bash
set -e

gcloud pubsub topics publish web-fetch --message="test!"
