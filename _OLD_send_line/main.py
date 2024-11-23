import logging
import urllib.request
import urllib.parse
import functions_framework
from flask import redirect, render_template
from common.utils import load_conf

APP_NAME = "send_line"
LINE_ENDPOINT = 'https://notify-api.line.me/api/notify'

LOG_LEVEL = logging.INFO


def send_message(conf: dict, target: str, message: str):

    headers = {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Authorization': 'Bearer {}'.format(conf["tokens"][target]),
    }
    data = {
        "message": message
    }

    # logging.info("target: {}, message: {}".format(target, message))
    # logging.info("headers: {}".format(headers))
    # logging.info("data: {}".format(urllib.parse.urlencode(data).encode()))
    req = urllib.request.Request(LINE_ENDPOINT,
                                 data=urllib.parse.urlencode(data).encode(),
                                 headers=headers, method="POST")
    with urllib.request.urlopen(req) as res:
        logging.info("response: {}".format(res.read()))


@functions_framework.http
def main(request):
    logging.basicConfig(format="[%(asctime)s] [%(levelname)s] %(message)s",
                        level=LOG_LEVEL, datefmt="%Y/%m/%d %H:%M:%S")
    logging.info("args: {}".format(request.args))

    conf = load_conf()

    body = request.get_data().decode("utf-8")
    logging.info("get_data: {}".format(body))

    qs = urllib.parse.parse_qs(body)
    logging.info("qs: {}".format(qs))
    target = qs["target"][0] if "target" in qs else ""
    message = qs["message"][0] if "message" in qs else ""
    logging.info("target: {}, message: {}".format(target, message))
    feedback = "Message successfully sent." if request.args.get("state", "") == "done" else ""

    if target and message:
        send_message(conf, target, message)
        return redirect(f"{APP_NAME}?state=done")

    return render_template("form.html", users=conf["tokens"].keys(), feedback=feedback)
