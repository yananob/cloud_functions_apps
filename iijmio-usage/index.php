<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Google\CloudFunctions\FunctionsFramework;
use CloudEvents\V1\CloudEventInterface;
use yananob\mytools\Logger;
use yananob\my_gcptools\CFUtils;
use yananob\mytools\Utils;
use yananob\mytools\Line;
use MyApp\IijmioUsage;

FunctionsFramework::cloudEvent('main', 'main');
function main(CloudEventInterface $event): void
{
    $logger = new Logger();

    $isLocal = CFUtils::isLocalEvent($event);
    $logger->log("Running as " . ($isLocal ? "local" : "cloud") . " mode");

    $config = Utils::getConfig(__DIR__ . "/configs/config.json");
    $iijmio = new IijmioUsage(
        $config["iijmio"], $config["alert"]["send_usage_each_n_days"]
    );
    $result = $iijmio->callApi();
    $alert_info = $iijmio->judgeResult($result);
    if ($alert_info["isSend"]) {
        $line = new Line(__DIR__ . '/configs/line.json');
        $line->sendMessage($config["alert"]["bot"], $config["alert"]["target"], $alert_info["message"]);
    }

    $logger->log($alert_info["message"]);
    $logger->log("Succeeded." . PHP_EOL);
}
