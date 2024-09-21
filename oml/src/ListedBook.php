<?php declare(strict_types=1);

namespace MyApp;

final class ListedBook extends OmlBook
{
    public string $reservedBookId;  // メモ：予約しても変わらない
    public string $authorForSearch;

    public function __construct(
        string $fullTitle, string $reservedBookId
    )
    {
        parent::__construct($fullTitle);
        $this->reservedBookId = $reservedBookId;
        $this->authorForSearch = preg_replace(['/\/.+/', '/ /'], '', $this->author);
    }

    public function toArray(): array
    {
        // return (array)$this;
        return [
            "title" => $this->title,
            "reserved_book_id" => $this->reservedBookId,
        ];
    }

    public static function fromArray(array $array): ListedBook
    {
        return new ListedBook(
            $array["title"], $array["reserved_book_id"]
        );
    }
}
