#!/usr/bin/env bash
set -e

SCRIPT_NAME=$1
export SCRIPT_NAME=$1
# remove "/" on the right side
SCRIPT_NAME=`php -r '$result=getenv("SCRIPT_NAME"); echo substr($result, -1) === "/" ? rtrim($result, "/") : $result;'`

echo "Checking $SCRIPT_NAME"
pushd ${SCRIPT_NAME}

# Check existance of specific deploy.sh
if test -f "deploy.sh"; then
    echo "Specific deploy.sh for this app exists. Please run it instead of this shell."
    exit 1
fi

# check existance of config.sample.json & config.json
if test -f "config.json.sample"; then
    if test ! -f "config.json"; then
        echo "Config.json.sample exists. Please make config.json for this app."
        exit 1
    fi
fi

echo "Starting to deploy $SCRIPT_NAME"

cp -rp ../common_py ./common

echo "-------- deploying http --------"
gcloud functions deploy ${SCRIPT_NAME} \
    --gen2 \
    --runtime=python311 \
    --region=us-west1 \
    --source=. \
    --entry-point=main \
    --trigger-http \
    --allow-unauthenticated \
    --max-instances 1

rm -rv ./common

popd
