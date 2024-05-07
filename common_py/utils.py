import json


def load_attributed_config(config_path: str = "config.json"):
    with open(config_path, "r") as f:
        config = json.load(f, object_hook=AttributedDict)
        return config


class AttributedDict(object):
    def __init__(self, obj):
        self._obj = obj

    def __getattr__(self, name):
        if name in self._obj:
            return self._obj.get(name)
        else:
            return None

    def fields(self):
        return self._obj

    def keys(self):
        return self._obj.keys()
