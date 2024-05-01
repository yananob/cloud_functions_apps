#!/bin/bash
set -e

gcloud pubsub topics publish oml-update --message="test!"
