<?php declare(strict_types=1);

namespace MyApp\common;

final class Trigger
{
    private $debugDate;

    public function __construct() {
        $this->debugDate = null;
    }

    public function setDebugDate(string $debugDate): void {
        $this->debugDate = date_create($debugDate);
    }

    private function getNow(): \DateTime
    {
        if ($this->debugDate == null) {
            return date_create("now", new \DateTimeZone("Asia/Tokyo"));
        }
        return $this->debugDate;
    }

    public function isLaunch(array $timing): bool
    {
        $now = $this->getNow();
        
        if (array_key_exists("weekdays", $timing)) {
            if (!in_array($now->format("D"), $timing["weekdays"])) {
                return false;
            }
        }
        if (array_key_exists("day", $timing)) {
            if ($timing["day"] != $now->format("d")) {
                return false;
            }
        }
        if (!array_key_exists("hour", $timing)) {
            throw new \Exception("hour should be specified: " . json_encode($timing));
        }
        if ($timing["hour"] != $now->format("H")) {
            return false;
        }

        return true;
    }

    public function isWatchTiming(): bool
    {
        $now = $this->getNow()->format("H:i");
        if (("06:30" <= $now) && ($now <= "23:30")) {
            return true;
        }
        return false;
    }
}
