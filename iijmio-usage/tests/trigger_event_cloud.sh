#!/bin/bash
set -e

gcloud pubsub topics publish iijmio-usage --message="test!"
