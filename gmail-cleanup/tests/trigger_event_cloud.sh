#!/bin/bash
set -e

gcloud pubsub topics publish gmail-cleanup --message="test!"
