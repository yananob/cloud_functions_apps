#!/bin/bash
set -eu

DEPLOY_DIR=_deploy
#export SCRIPT_NAME=$1
# remove "/" on the right side
SCRIPT_NAME=daily-quote

echo "Checking $SCRIPT_NAME"
# pushd $SCRIPT_NAME

# Check existance of .gcloudignore
if ! test -f ".gcloudignore"; then
    echo ".gcloudignore doesn't exist. Please create it."
    exit 1
fi

# # Check existance of specific deploy.sh
# if test -f "deploy.sh"; then
#     echo "Specific deploy.sh for this app exists. Please run it instead of this shell."
#     exit 1
# fi

# check existance of config.sample.json & config.json
if test -f "config.json.sample"; then
    if test ! -f "config.json"; then
        echo "Config.json.sample exists. Please make config.json for this app."
        exit 1
    fi
fi
# popd
cd ..

echo "Starting to deploy $SCRIPT_NAME"

mkdir -p $DEPLOY_DIR
pushd $DEPLOY_DIR

rm -rf ./$SCRIPT_NAME
rsync -vaL --exclude-from=../_misc/deploy/rsync_exclude.conf ../$SCRIPT_NAME ./

pushd $SCRIPT_NAME

echo "-------- deploying topic --------"
gcloud functions deploy $SCRIPT_NAME \
    --gen2 \
    --runtime=php82 \
    --region=us-west1 \
    --source=. \
    --entry-point=main \
    --trigger-topic=$SCRIPT_NAME \
    --max-instances 1

echo "-------- deploying http --------"
gcloud functions deploy $SCRIPT_NAME-editor \
    --gen2 \
    --runtime=php82 \
    --region=us-west1 \
    --source=. \
    --entry-point=editor \
    --trigger-http \
    --allow-unauthenticated \
    --max-instances 1

popd
popd
