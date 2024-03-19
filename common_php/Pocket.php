<?php declare(strict_types=1);

namespace MyApp\common;

final class Pocket
{
    private string $consumer_key;
    private string $access_token;

    public function __construct() {
        $config = Utils::getConfig(dirname(__FILE__) . "/config/config_pocket.json");
        $this->consumer_key = $config["consumer_key"];
        $this->access_token = $config["access_token"];
    }

    public function add(string $url)
    {
        $headers = [
            'contentType' => "application/x-www-form-urlencoded; charset=UTF8",
            'X-Accept' => "application/x-www-form-urlencoded",
        ];
        $fields = [
            "consumer_key" => $this->consumer_key,
            "access_token" => $this->access_token,
            'url' => $url,
        ];
        $ch = curl_init();
        curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_URL => "https://getpocket.com/v3/add",
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => $fields,
        ]);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ($httpcode != "200") {
            throw new \Exception("Failed to add url [{$url}]. Http response code: [{$httpcode}]");
        }
        curl_close($ch);
    }
}
