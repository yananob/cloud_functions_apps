<?php declare(strict_types=1);

namespace MyApp\common;

final class LINE
{
    private array $tokens;

    public function __construct() {
        $config = Utils::getConfig(__DIR__ . "/configs/config_line.json");
        $this->tokens = $config["tokens"];
    }

    public function sendMessage(string $target, string $message): void {
        if (!array_key_exists($target, $this->tokens)) {
            throw new \Exception("Unknown target: {$target}");
        }

        $headers = [
            "contentType: application/x-www-form-urlencoded",
            "Authorization: Bearer {$this->tokens[$target]}",
        ];
        $fields = [
            "message" => $message,
        ];
        $ch = curl_init();
        curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_URL => "https://notify-api.line.me/api/notify",
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => $fields,
        ]);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ($httpcode != "200") {
            throw new \Exception("Failed to send message [to: {$target}, message: {$message}]. Http response code: [{$httpcode}]");
        }
        curl_close($ch);
    }
}
