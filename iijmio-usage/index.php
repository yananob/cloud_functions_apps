<?php declare(strict_types=1);

use CloudEvents\V1\CloudEventInterface;
use Google\CloudFunctions\FunctionsFramework;
use MyApp\IijmioUsage;
use MyApp\common\Utils;
use MyApp\common\Logger;
use MyApp\common\LINE;

FunctionsFramework::cloudEvent('main', 'main');
function main(CloudEventInterface $event): void
{
    $logger = new Logger();
    # disabled to run locally
    # (when launched from local, $event parameter becomes just a string and cause an error)
    // $cloudEventData = $event->getData();
    // $logger->log("cloudEventData: {$cloudEventData}");
    // $pubSubData = base64_decode($cloudEventData['message']['data']);
    // $logger->log("message: {$pubSubData}");

    $config = Utils::getConfig(__DIR__ . "/config/config.json");
    $iijmio = new IijmioUsage(
        $config["iijmio"], $config["alert"]["send_usage_each_n_days"]
    );
    $result = $iijmio->callApi();
    $alert_info = $iijmio->judgeResult($result);
    if ($alert_info["isSend"]) {
        $line = new LINE();
        $line->sendMessage($config["alert"]["target"], $alert_info["message"]);
    }

    $logger->log($alert_info["message"]);
    $logger->log("Succeeded." . PHP_EOL);
}
