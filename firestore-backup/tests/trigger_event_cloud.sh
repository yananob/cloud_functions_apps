#!/bin/bash
set -eu

gcloud pubsub topics publish firestore-backup --message="test!"
