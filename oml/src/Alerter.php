<?php declare(strict_types=1);

namespace MyApp;

use yananob\MyTools\Line;
use MyApp\AlertType;
use MyApp\BookState;

final class Alerter
{
    private string $bot;
    private string $target;
    private array $alerts;
    private $debugDate;

    public function __construct(array $config, private string $baseUrl)
    {
        $this->alerts = [];
        $this->debugDate = null;
        $this->bot = $config["line_bot"];
        $this->target = $config["line_target"];
    }

    public function setDebugDate(string $debugDate): void
    {
        $this->debugDate = (new \Datetime($debugDate))->setTime(0, 0, 0);
    }

    private function _today(): \Datetime
    {
        if (is_null($this->debugDate)) {
            return (new \Datetime())->setTime(0, 0, 0);
        }
        else {
            return $this->debugDate;
        }
    }

    public function addAlert(AlertType $type, string $title, string $info = null): void
    {
        $this->alerts[] = [
            "type" => $type,
            "title" => $title,
            "info" => $info,
        ];
    }

    public function getMessages(): array
    {
        $result = [];
        foreach ($this->alerts as $alert) {
            $message = "";

            switch ($alert["type"]) {
                case AlertType::DuplicatedReserved:
                    $message = "【予約の重複】";
                    break;
                case AlertType::DuplicatedReservedAndLending:
                    $message = "【予約と貸出の重複】";
                    break;
                case AlertType::ReturnLimit:
                    $message = "【要返却】";
                    break;
                case AlertType::KeepLimit:
                    $message = "【要受取】";
                    break;
                case AlertType::AutoExtended:
                    $message = "【自動延長済】";
                    break;
                default:
                    throw new \Exception("Unkown alert type: {$alert['type']}");
            }

            $message .= $alert["title"];
            if (!empty($alert["info"])) {
                $message .= "({$alert['info']})";
            }

            $result[] = $message;
        }

        return $result;
    }

    public function sendAlerts(): void
    {
        $alerts = implode("\n\n", $this->getMessages());
        $message = <<<EOT
oml books アラート:

{$alerts}

{$this->baseUrl}
EOT;
        $line = new Line(__DIR__ . '/../configs/line.json');
        $line->sendMessage($this->bot, $this->target, $message);
    }

    public function checkAll(array $reserved_books, array $lending_books): void
    {
        $this->checkKeepLimitDate($reserved_books);
        $this->checkReturnLimitDate($lending_books);
        $this->checkDuplicates($reserved_books, $lending_books);
    }

    public function checkDuplicates(array $reserved_books, array $lending_books): void
    {
        // check reserved books
        for ($idx = 0; $idx < count($reserved_books); $idx++) {
            $base_book = $reserved_books[$idx];
            foreach (array_slice($reserved_books, $idx + 1) as $check_book) {
                // オーナーが同一の場合は無視（雑誌など、実際には重複でない場合）
                if ($base_book->owner === $check_book->owner) {
                    continue;
                }

                if ($base_book->title === $check_book->title) {
                    // reserved_owner: ブランクの場合は0になっている前提にする（DB書き込み時）
                    $ng_owner = ($base_book->reservedOrder > $check_book->reservedOrder) ? $base_book->owner : $check_book->owner;
                    $this->addAlert(AlertType::DuplicatedReserved, $base_book->title, "後予約@" . substr($ng_owner, -2));
                }
            }
        }

        // check reserved vs lending books
        foreach ($reserved_books as $reserved_book) {
            foreach ($lending_books as $lending_book) {
                if ($reserved_book->title === $lending_book->title) {
                    $this->addAlert(AlertType::DuplicatedReservedAndLending, $reserved_book->title, "予約@" . substr($reserved_book->owner, -2));
                }
            }
        }
    }

    public function checkReturnLimitDate(array $lending_books): void
    {
        foreach ($lending_books as $lending_book) {
            // diff = today - return_limit_date
            $diff_days = $this->_today()->diff(new \Datetime($lending_book->returnLimitDate))->format("%r%a");
            if (($diff_days <= 2)
             && in_array($lending_book->state, [BookState::Extended, BookState::Reserved, BookState::Overdue])) {
                $this->addAlert(AlertType::ReturnLimit, $lending_book->title);
           }
        }
    }

    public function checkKeepLimitDate(array $reserved_books): void
    {
        foreach ($reserved_books as $reserved_book) {
            // diff = today - keep_limit_date
            $diff_days = $this->_today()->diff(new \Datetime($reserved_book->keepLimitDate))->format("%r%a");
            if (($diff_days <= 2)
             && in_array($reserved_book->state, [BookState::Keeping, BookState::Expired])) {
                $this->addAlert(AlertType::KeepLimit, $reserved_book->title);
           }
        }
    }

}
