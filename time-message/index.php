<?php declare(strict_types=1);

use CloudEvents\V1\CloudEventInterface;
use Google\CloudFunctions\FunctionsFramework;
use MyApp\common\Logger;
use MyApp\common\Utils;
use MyApp\common\LINE;
use MyApp\common\Trigger;

FunctionsFramework::cloudEvent('main', 'main');
function main(CloudEventInterface $event): void
{
    $logger = new Logger("time-message");
    $trigger = new Trigger();
    $line = new LINE();

    $config = Utils::getConfig(dirname(__FILE__) . "/configs/config.json");
    foreach ($config["settings"] as $setting) {
        $logger->log("Processing target: " . json_encode($setting));

        if ($trigger->isLaunch($setting["timing"])) {
            $logger->log("Sending message");
            $line->sendMessage($setting["target"], $setting["message"]);
        }
    };

    $logger->log("Succeeded.");
}
