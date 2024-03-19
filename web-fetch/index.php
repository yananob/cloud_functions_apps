<?php declare(strict_types=1);

use CloudEvents\V1\CloudEventInterface;
use Google\CloudFunctions\FunctionsFramework;
use MyApp\common\Logger;
use MyApp\common\Utils;
use MyApp\common\Trigger;
use MyApp\common\Pocket;

FunctionsFramework::cloudEvent('main', 'main');
function main(CloudEventInterface $event): void
{
    $logger = new Logger("web-fetch");
    $trigger = new Trigger();

    $config = Utils::getConfig(dirname(__FILE__) . "/configs/config.json");
    foreach ($config["settings"] as $setting) {
        $logger->log("Processing target: " . json_encode($setting));

        if ($trigger->isLaunch($setting["timing"])) {
            $logger->log("Adding page to Pocket");
            $pocket = new Pocket();
            $pocket->add($setting["url"]);
        }
    };

    $logger->log("Succeeded.");
}
