#!/bin/bash
set -e

source ../../common.sh

gcloud artifacts repositories set-cleanup-policies gcf-artifacts \
    --project=${PROJECT_ID} \
    --location=us-west1 \
    --policy=policy.json \
    --no-dry-run
    # --dry-run

    # --overwrite \
    # --verbosity debug \

gcloud artifacts repositories list-cleanup-policies gcf-artifacts \
    --project=${PROJECT_ID} \
    --location=us-west1
