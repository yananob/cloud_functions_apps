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
    $logger->log(str_repeat("-", 120));

    $logger->log("headers: " . json_encode($request->getHeaders()));

    $logger->log("params: " . json_encode($request->getQueryParams()));

    $logger->log("parsedBody: " . json_encode($request->getParsedBody()));

    $body = $request->getBody()->getContents();
    $logger->log("body: " . $body);
    $logger->log("body_json: " . json_encode(json_decode($body)));

    // sample:
    /* body: 
    {
        "destination": "XXXX",
        "events" : [
            {
                "type": "message",
                "message": {
                    "type":"text",
                    "id":"XXXX",
                    "quoteToken":"XXXX",
                    "text":"わわわわわ"
                },
                "webhookEventId": "XXXX",
                "deliveryContext": {
                    "isRedelivery": false
                },
                "timestamp":1731926933496,
                "source":{
                    "type":"user",
                    "userId":"XXXX"
                },
                "replyToken":"XXXX",
                "mode":"active"
            }
        ]
    }
    */

    $headers = ['Content-Type' => 'application/json'];
    return new Response(200, $headers, json_encode($body));
}
