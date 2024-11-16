<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Google\CloudFunctions\FunctionsFramework;
use CloudEvents\V1\CloudEventInterface;
use yananob\mytools\Logger;
use yananob\mytools\Utils;
use yananob\mytools\Trigger;
use yananob\mytools\Line;

FunctionsFramework::cloudEvent('main', 'main');
function main(CloudEventInterface $event): void
{
    $logger = new Logger("time-message");
    $trigger = new Trigger();
    $line = new Line(__DIR__ . '/configs/line.json');

    $config = Utils::getConfig(__DIR__ . "/configs/config.json");
    foreach ($config["settings"] as $setting) {
        $logger->log("Processing target: " . json_encode($setting));

        if ($trigger->isLaunch($setting["timing"])) {
            $logger->log("Sending message");
            $line->sendMessage($setting["bot"], $setting["target"], $setting["message"]);
        }
    };

    $logger->log("Succeeded.");
}
