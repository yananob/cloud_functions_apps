<?php declare(strict_types=1);

namespace MyApp;

final class SearchedBook extends OmlBook
{
    public string $reservedBookId;  // メモ：予約しても変わらない

    public function __construct(
        string $fullTitle, string $reservedBookId
    )
    {
        parent::__construct($fullTitle);
        $this->reservedBookId = $reservedBookId;
    }

    public function toArray(): array
    {
        // return (array)$this;
        return [
            "title" => $this->title,
            "reserved_book_id" => $this->reservedBookId,
        ];
    }

    public static function fromArray(array $array): SearchedBook
    {
        return new SearchedBook(
            $array["title"], $array["reserved_book_id"]
        );
    }
}
