#!/bin/bash

export PYTHONPATH=..:$PYTHONPATH

pytest -vv ./

# pytest -vv ./test_main.py
# pytest -vv ./test_alerter.py
