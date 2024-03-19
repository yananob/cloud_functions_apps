# -*- coding: utf-8 -*-

import sys
import json
import logging
from enum import Enum
from html.parser import HTMLParser
import urllib.request
import urllib.parse
from flask import Flask, request, Response
import arrow
import functions_framework

YAHOO_ENDPOINT = 'https://transit.yahoo.co.jp/search/print'

LOG_LEVEL = logging.INFO


class RESPONSE_TYPE(Enum):
    NORMAL = "normal"
    VERBOSE = "verbose"


class YahooTransitParser(HTMLParser):
    def __init__(self):
        HTMLParser.__init__(self)
        self.found = False
        self.count = 0
        self.next_time = ""

    def handle_starttag(self, tag, attrs):
        if self.count == 0:
            if tag == "div":
                for attr in attrs:
                    if attr == ("id", "srline"):
                        self.count += 1
        elif self.count == 1:
            if tag == "li":
                for attr in attrs:
                    if attr == ("class", "time"):
                        self.count += 1
        elif self.count == 2:
            if tag == "span":
                self.count = 99
                self.found = True

    def handle_data(self, data):
        if self.found:
            self.next_time = data
            self.found = False

    def get_next_time(self):
        return self.next_time


def send_request(sta_from, sta_to):

    data = {
        "from": sta_from,
        "to": sta_to,
        "type": "1",
        
        "flatlon": "",
        "tlatlon": "",
        "viacode": "",
        "shin": "1",
        "ex": "1",
        "hb": "1",
        "al": "1",
        "lb": "1",
        "sr": "1",
        "ws": "3",
        "s": "0",
        "ei": "",
        "fl": "1",
        "tl": "3",
        "expkind": "1",
        "mtf": "",
        "out_y": "",
        "mode": "",
        "c": "",
        "searchOpt": "",
        "stype": "",
        "ticket": "ic",
        "userpass": "1",
        "passtype": "",
        "detour_id": "",
        "no": "1",
    }

    logging.info("data: {}".format(urllib.parse.urlencode(data).encode()))
    req = urllib.request.Request("{}?{}".format(YAHOO_ENDPOINT, urllib.parse.urlencode(data)))

    response = urllib.request.urlopen(req)

    body = response.read().decode('utf-8')
    # with open("test.html", "w") as f:
    #     f.write(body)

    return body


def parse_response(sta_from, sta_to):
    parser = YahooTransitParser()
    parser.feed(send_request(sta_from, sta_to))
    next_time_str = parser.get_next_time()  # value is like "19:34発→"
    if not next_time_str:
        raise Exception("Cannot get next_time. Please check the parameters.")
    logging.debug("response: {}".format(next_time_str))

    next_time_str = next_time_str.replace("発", "").replace("→", "")
    logging.debug("next_time_str: {}".format(next_time_str))
    now = arrow.now().to('Asia/Tokyo')
    next_time = now.replace(hour=int(next_time_str[0:2]),
                            minute=int(next_time_str[3:5]),
                            second=0)
    logging.debug("next_time: {}".format(next_time))

    diff_time = next_time - now
    logging.debug("diff_time: {}".format(diff_time))

    return next_time, diff_time


@functions_framework.http
def main(req):
    logging.basicConfig(format="[%(asctime)s] [%(levelname)s] %(message)s",
                        level=LOG_LEVEL, datefmt="%Y/%m/%d %H:%M:%S")
    logging.info("args: {}".format(req.args))
    message = ""
    content_type = "text/plain"
    try:
        res_type = req.args.get("res_type", RESPONSE_TYPE.NORMAL)
        sta_from = req.args.get("from", "")
        sta_to = req.args.get("to", "")
        if (not sta_from) or (not sta_to):
            raise Exception("Please set from and to parameters.")

        next_time, diff_time = parse_response(sta_from, sta_to)
        dm = divmod(diff_time.total_seconds(), 60)
        logging.debug("divmod: {}".format(dm))
        diff_time_str = "{}:{:02d}".format(int(dm[0]), int(dm[1]))

        if res_type == RESPONSE_TYPE.VERBOSE:
            message_json = {
                "station_from": sta_from,
                "station_to": sta_to,
                "next_time": next_time.format("HH:mm"),
                "diff_seconds": int(diff_time.total_seconds()),
                "diff_time": diff_time_str,
            }
            message = json.dumps(message_json, ensure_ascii=False)
            content_type = "application/json"
        else:
            message = diff_time_str

    except Exception:
        ex, ms, tb = sys.exc_info()
        message = f"Error occured. <{ms}>"
        logging.error(ms)

    finally:
        logging.info("message: {}".format(message))
        resp = Response(message)
        resp.headers["content-type"] = content_type
        return resp
