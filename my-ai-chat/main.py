import os
import sys
import logging
import json
import traceback
import flask
import functions_framework
import requests

from common.utils import load_attributed_config

LOG_LEVEL = logging.DEBUG


def send_message(api_key: str, model: str, message: str):
    headers = {
        "Content-Type": "application/json",
        "Authorization": f"Bearer {api_key}",
    }
    payload = {
        "model": model,
        "messages": [
            {
                "role": "system",
                "content": "You are a helpful assistant.",
            },
            {
                "role": "user",
                "content": message,
            },
        ],
    }
    logging.debug(f"payload: {json.dumps(payload)}")
    r = requests.post(
        "https://api.openai.com/v1/chat/completions",
        headers=headers,
        data=json.dumps(payload))
    return r


@functions_framework.http
def main(request):
    logging.basicConfig(
        format="[%(asctime)s] [%(levelname)s] %(message)s",
        level=LOG_LEVEL, datefmt="%Y/%m/%d %H:%M:%S")

    logging.info(f"data: {request.form.to_dict()}")

    config = load_attributed_config(os.path.join("configs", "config.json"))

    data = request.form.to_dict()
    # if request.method == "GET":
    # if message is None:
        # return flask.render_template("form.html", response=response)

    # elif request.method == "POST":
    question = ""
    answer = ""
    if data:
        question = data["question"]
        answer = send_message(config.api_key, config.model, question)
        logging.info(f"answer: {answer.json()}")
        answer = answer.json()["choices"][0]["message"]["content"]
        # response = flask.Response(json.dumps(message_json, ensure_ascii=False))
        # response.headers["content-type"] = "application/json"
        # return response

    # response = flask.Response()
    # response.headers["content-type"] = "text/html"
    return flask.render_template("form.html", question=question, answer=answer)

    # else:
    #     raise Exception(f"Unknown method: {request.method}")
