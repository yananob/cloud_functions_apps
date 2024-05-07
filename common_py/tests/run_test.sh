#!/bin/bash
set -e

export PYTHONPATH=..:$PYTHONPATH

pytest -vv ./

# pytest -vv ./test_main.py
# pytest -vv ./test_alerter.py
