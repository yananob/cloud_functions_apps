#!/bin/bash
set -eu

gcloud pubsub topics publish oml-update --message="test!"
