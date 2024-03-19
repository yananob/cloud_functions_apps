import json

def load_conf(config_path: str = "config.json"):
    with open(config_path, "r") as f:
        conf = json.load(f)
        return conf
