import os
import json
import pytest
import utils


def _write_config(filename: str, config: dict) -> None:
    with open(filename, "w") as f:
        f.write(json.dumps(config))


CASES_LOAD_CONF = [
    {
        "filename": None,
    },
    {
        "filename": "config_alt.json",
    },
]

@pytest.mark.parametrize("test_case", CASES_LOAD_CONF)
def test_load_conf(test_case):
    target_filename = test_case["filename"] if test_case["filename"] else "config.json"
    print(target_filename)
    try:
        _write_config(
            target_filename,
            {
                "hoge1": "hage1",
                "hoge2": "hage2"
            },
        )

        if test_case["filename"]:
            conf = utils.load_conf(test_case["filename"])
        else:
            conf = utils.load_conf()

        assert conf["hoge1"] == "hage1"
        assert conf["hoge2"] == "hage2"

    finally:
        os.remove(target_filename)
