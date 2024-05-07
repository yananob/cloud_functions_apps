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
def test_load_attributed_config(test_case):
    target_filepath = os.path.join(
        "configs",
        test_case["filename"] if test_case["filename"] else "config.json"
    )

    try:
        _write_config(
            target_filepath,
            {
                "strk1": "strv1",
                "array1": ["av1", "av2"],
                "dict1":  {"dk1": "dv1"},
                "dict2": [
                    {
                        "dk2": "dv2"
                    }
                ]
            }
        )
        config = utils.load_attributed_config(target_filepath)

        assert config.strk1 == "strv1"
        assert config.array1 == ["av1", "av2"]
        assert config.dict1.dk1 == "dv1"
        assert config.dict2[0].dk2 == "dv2"

    finally:
        os.remove(target_filepath)
