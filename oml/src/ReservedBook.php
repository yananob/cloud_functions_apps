<?php declare(strict_types=1);

namespace MyApp;

final class ReservedBook extends OmlBook
{
    public string $owner;
    public int $reservedOrder;
    public string $reservedBookId;
    public string $reservedDate;
    public BookState $state;
    public string $keepLimitDate;
    public string $changingId;

    public function __construct(
        string $owner, string $fullTitle, int $reservedOrder, string $reservedBookId,
        string $reservedDate, BookState $state, string $keepLimitDate, string $changingId
    ) {
        parent::__construct($fullTitle);

        $this->owner = $owner;
        $this->reservedOrder = $reservedOrder;
        $this->reservedBookId = $reservedBookId;
        $this->reservedDate = $reservedDate;
        $this->state = $state;
        $this->keepLimitDate = $keepLimitDate;
        $this->changingId = $changingId;
    }

    public function toArray(): array
    {
        // return (array)$this;
        return [
            "owner" => $this->owner,
            "title" => $this->fullTitle,
            "reserved_order" => $this->reservedOrder,
            "reserved_book_id" => $this->reservedBookId,
            "reserved_date" => $this->reservedDate,
            "state" => $this->state->value,
            "keep_limit_date" => $this->keepLimitDate,
            "changing_id" => $this->changingId,
        ];
    }

    public static function fromArray(array $array): ReservedBook
    {
        return new ReservedBook(
            $array["owner"], $array["title"], $array["reserved_order"], $array["reserved_book_id"],
            $array["reserved_date"], BookState::from($array["state"]), $array["keep_limit_date"], $array["changing_id"]
        );
    }
}
