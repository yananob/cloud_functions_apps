<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

Google\CloudFunctions\FunctionsFramework::cloudEvent('main', 'main');
function main(\CloudEvents\V1\CloudEventInterface $event): void
{
    $logger = new yananob\mytools\Logger();

    $isLocal = yananob\my_gcptools\GcpUtils::isLocalEvent($event);
    $logger->log("Running as " . ($isLocal ? "local" : "cloud") . " mode");

    $config = yananob\mytools\Utils::getConfig(__DIR__ . "/configs/config.json");
    $iijmio = new MyApp\IijmioUsage(
        $config["iijmio"], $config["alert"]["send_usage_each_n_days"]
    );
    $result = $iijmio->callApi();
    $alert_info = $iijmio->judgeResult($result);
    if ($alert_info["isSend"]) {
        $line = new \yananob\mytools\Line(__DIR__ . '/configs/line.json');
        $line->sendMessage($config["alert"]["target"], $alert_info["message"]);
    }

    $logger->log($alert_info["message"]);
    $logger->log("Succeeded." . PHP_EOL);
}
