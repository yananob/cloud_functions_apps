<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

Google\CloudFunctions\FunctionsFramework::cloudEvent('main', 'main');
function main(CloudEvents\V1\CloudEventInterface $event): void
{
    $logger = new yananob\MyTools\Logger("web-fetch");
    $trigger = new yananob\MyTools\Trigger();

    $config = yananob\MyTools\Utils::getConfig(dirname(__FILE__) . "/configs/config.json");
    foreach ($config["settings"] as $setting) {
        $logger->log("Processing target: " . json_encode($setting));

        if ($trigger->isLaunch($setting["timing"])) {
            $logger->log("Adding page to Pocket");
            $pocket = new yananob\MyTools\Pocket(__DIR__ . '/configs/pocket.json');
            $pocket->add($setting["url"]);
        }
    };

    $logger->log("Succeeded.");
}
