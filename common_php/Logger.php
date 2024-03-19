<?php declare(strict_types=1);

namespace MyApp\common;

final class Logger
{
    private $fp;
    private string $title;

    public function __construct(string $title = "")
    {
        $this->fp = fopen(getenv('LOGGER_OUTPUT') ?: 'php://stderr', 'wb');
        $this->title = $title;
    }

    public function log(string|array $message): void
    {
        if (gettype($message) === "array") {
            $message = json_encode($message);
        }
        $log_message = $message;
        if (!empty($this->title)) {
            $log_message = "[{$this->title}] {$log_message}";
        }

        fwrite($this->fp, $log_message . PHP_EOL);
    }
}
