#!/bin/bash
set -e

gcloud pubsub topics publish firestore-backup --message="test!"
