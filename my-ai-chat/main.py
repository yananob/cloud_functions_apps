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

    try:
        logging.info(f"data: {request.data}")

        config = load_attributed_config(os.path.join("configs", "config.json"))

        # message = request.args.get("message", "")
        data = json.loads(request.data.decode("utf-8"))
        message = data["message"]
        answer = send_message(config.api_key, config.model, message)
        logging.info(f"answer: {answer.text}")

        message_json = {
            "message": message,
            "answer": answer.text,
        }
        message = json.dumps(message_json, ensure_ascii=False)

    except Exception:
        ex, ms, tb = sys.exc_info()
        logging.error(f"Error: <{ms}>")
        traceback.print_tb(tb)
        message = "Error!"

    finally:
        logging.info(f"message: {message}")
        response = flask.Response(message)
        response.headers["content-type"] = "application/json"
        return response
