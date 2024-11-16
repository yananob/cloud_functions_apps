<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use GuzzleHttp\Psr7\Response;
use yananob\mytools\Logger;

Google\CloudFunctions\FunctionsFramework::http('main', 'main');
function main(ServerRequestInterface $request): ResponseInterface
{
    $logger = new Logger("webhook-receive");
    $params = $request->getQueryParams();
    $params = array_merge($params, $request->getParsedBody());
    $logger->log(str_repeat("-", 120));
    $logger->log("Params: " . json_encode($params));

    $headers = ['Content-Type' => 'application/json'];
    return new Response(200, $headers, json_encode($params));
}
