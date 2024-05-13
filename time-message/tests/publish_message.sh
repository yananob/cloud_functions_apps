#!/bin/bash
set -eu

gcloud pubsub topics publish time-message --message="test!"
