<?php declare(strict_types=1);

namespace MyApp;

final class LendingBook extends OmlBook
{
    private $__debugDate;

    public string $owner;
    public string $returnLimitDate;
    public BookState $state;
    public string $lendingBookId;

    public function __construct(
        string $owner, string $fullTitle, string $returnLimitDate, BookState $state, string $lendingBookId
    ) {
        parent::__construct($fullTitle);

        $this->owner = $owner;
        $this->fullTitle = $fullTitle;
        $this->returnLimitDate = $returnLimitDate;
        $this->state = $state;
        $this->lendingBookId = $lendingBookId;
    }

    public function setDebugDate(string $debugDate): void
    {
        $this->__debugDate = (new \Datetime($debugDate))->setTime(0, 0, 0);
    }

    private function __today(): \Datetime
    {
        if (is_null($this->__debugDate)) {
            return (new \Datetime())->setTime(0, 0, 0);
        }
        else {
            return $this->__debugDate;
        }
    }

    public function toArray(): array
    {
        // return (array)$this;
        return [
            "owner" => $this->owner,
            "title" => $this->fullTitle,
            "return_limit_date" => $this->returnLimitDate,
            "state" => $this->state->value,
            "lending_book_id" => $this->lendingBookId,
        ];
    }

    public static function fromArray(array $array): LendingBook
    {
        return new LendingBook(
            $array["owner"], $array["title"], $array["return_limit_date"], BookState::from($array["state"]), $array["lending_book_id"]
        );
    }

    public function isReturndateCame(): bool
    {
        $today = $this->__today()->format("Y/m/d");
        return $this->returnLimitDate <= $today;
    }

    public function isExtendable(): bool
    {
        return in_array($this->state, [BookState::None]);
    }
}
