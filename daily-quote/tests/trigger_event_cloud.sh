#!/bin/bash
set -e

gcloud pubsub topics publish daily-quote --message="test!"
