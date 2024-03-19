import json
import logging
from google.cloud import pubsub_v1
import functions_framework
from common.utils import load_conf

LOG_LEVEL = logging.INFO

@functions_framework.http
def main(request):
    conf = load_conf()

    logging.basicConfig(format="[%(asctime)s] [%(levelname)s] %(message)s",
                        level=LOG_LEVEL, datefmt="%Y/%m/%d %H:%M:%S")
    logging.info("args: {}".format(request.args))
    topic = request.args.get('topic')
    
    publisher = pubsub_v1.PublisherClient()
    topic_path = publisher.topic_path(conf["project_id"], topic)

    message = json.dumps(request.args)
    # Data must be a bytestring
    data = message.encode("utf-8")
    future = publisher.publish(topic_path, data)
    result = f"Topic: {topic}, Result: {future.result()}"
    logging.info(result)

    return result
