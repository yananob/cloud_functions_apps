<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use yananob\mytools\Logger;

Google\CloudFunctions\FunctionsFramework::http('main', 'main');
function main(Psr\Http\Message\ServerRequestInterface $request): string
{
    $logger = new Logger("webhook-receive");
    $params = $request->getQueryParams();
    $params = array_merge($params, $request->getParsedBody());
    $logger->log(str_repeat("-", 120));
    $logger->log("Params: " . json_encode($params));

    return json_encode($params);
}
