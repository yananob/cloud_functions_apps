#!/bin/bash
set -e

gcloud pubsub topics publish time-message --message="test!"
