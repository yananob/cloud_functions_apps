<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Google\CloudFunctions\FunctionsFramework;
use Psr\Http\Message\ServerRequestInterface;
use yananob\mytools\Logger;
use yananob\mytools\Line;
use yananob\my_gcptools\CFUtils;

FunctionsFramework::http('main', 'main');
function main(ServerRequestInterface $request): string
{
    $logger = new Logger("line-send");
    $query = $request->getQueryParams();
    $body = $request->getParsedBody();
    $logger->log(str_repeat("-", 120));
    $logger->log("Query: " . json_encode($query));
    $logger->log("Body: " . json_encode($body));

    // $smarty = new Smarty();
    // $smarty->setTemplateDir(__DIR__ . "/templates");

    $isLocal = CFUtils::isLocalHttp($request);
    $logger->log("Running as " . ($isLocal ? "local" : "cloud") . " mode");

    if (empty($body)) {
        // TODO: GUIの表示
        $logger->log("GET");

        return "Done";
    } else {
        $logger->log("POST");
        if (!array_key_exists("target", $body)) {
            throw new \Exception("target was not passed");
        }
        if (!array_key_exists("message", $body)) {
            throw new \Exception("message was not passed");
        }
        $target = $body["target"];
        $message = $body["message"];

        $line = new Line(__DIR__ . "/configs/line.json");
        // MEMO: UIのシンプル化のために、botとtargetが同じであることを前提にしている
        $line->sendMessage($target, $target, $message);

        return "";
    }
}
