<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

Google\CloudFunctions\FunctionsFramework::cloudEvent('main', 'main');
function main(CloudEvents\V1\CloudEventInterface $event): void
{
    $logger = new yananob\mytools\Logger("time-message");
    $trigger = new yananob\mytools\Trigger();
    $line = new yananob\mytools\Line();

    $config = yananob\mytools\Utils::getConfig(dirname(__FILE__) . "/configs/config.json");
    foreach ($config["settings"] as $setting) {
        $logger->log("Processing target: " . json_encode($setting));

        if ($trigger->isLaunch($setting["timing"])) {
            $logger->log("Sending message");
            $line->sendMessage($setting["target"], $setting["message"]);
        }
    };

    $logger->log("Succeeded.");
}
