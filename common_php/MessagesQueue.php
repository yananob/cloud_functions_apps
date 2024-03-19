<?php declare(strict_types=1);

namespace MyApp\common;

final class MessagesQueue
{
    private const TYPE_MESSAGES = "messages";
    private const TYPE_ALERTS = "alerts";
    private const TYPE_ERRORS = "errors";

    public function __construct()
    {
    }

    public function pushMessage(string $message): void
    {
        $this->__push(self::TYPE_MESSAGES, $message);
    }

    public function pushAlert(string $message): void
    {
        $this->__push(self::TYPE_ALERTS, $message);
    }

    public function pushError(string $message): void
    {
        $this->__push(self::TYPE_ERRORS, $message);
    }

    private function __push(string $type, string $message): void
    {
        $current = [];
        if (isset($_SESSION[$type])) {
            $current = $_SESSION[$type];
        }
        $current[] = $message;
        $_SESSION[$type] = $current;
    }

    public function popMessages(): array
    {
        return $this->__pop(self::TYPE_MESSAGES);
    }

    public function popAlerts(): array
    {
        return $this->__pop(self::TYPE_ALERTS);
    }

    public function popErrors(): array
    {
        return $this->__pop(self::TYPE_ERRORS);
    }

    private function __pop(string $type): array
    {
        $result = [];

        if (isset($_SESSION[$type])) {
            $result = $_SESSION[$type];
            unset($_SESSION[$type]);
        }
        return $result;
    }
}