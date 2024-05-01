import sys
import logging
import json
import urllib
import flask
import functions_framework
import requests

LOG_LEVEL = logging.INFO

def send_message(api_key: str, model: str, message: str):
    headers = {
        "Authorization": f"Bearer {api_key}",
    }
    payload = {
        "messages": [message],
        "model": model,
    }
    r = requests.post("https://api.openai.com/v1/chat/completions", headers=headers, data=payload)
    return r

@functions_framework.http
def main(request):
    logging.basicConfig(format="[%(asctime)s] [%(levelname)s] %(message)s",
                        level=LOG_LEVEL, datefmt="%Y/%m/%d %H:%M:%S")

    try:
        logging.info(f"args: {request.args}")

        with open("./configs/config.json", "r") as f:
            config = json.load(f)

        message = request.args.get("message", "")
        answer = send_message(config["api_key"], config["model"], message)
        logging.debug(f"answer: {answer}")

        message_json = {
            "message": message,
            "answer": answer,
        }
        message = json.dumps(message_json, ensure_ascii=False)
        content_type = "application/json"

    except Exception:
        ex, ms, tb = sys.exc_info()
        message = f"Error: <{ms}>"
        logging.error(ms)

    finally:
        logging.info(f"message: {message}")
        response = flask.Response(message)
        response.headers["content-type"] = content_type
        return response
