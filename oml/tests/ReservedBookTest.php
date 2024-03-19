<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use MyApp\common\Utils;
use MyApp\BookState;
use MyApp\ReservedBook;

final class ReservedBookTest extends TestCase
{
    private ReservedBook $book;

    private const TEST_BOOK = [
        "owner" => "TEST_OWNER",
        "title" => "[雑誌][巻号] kodomoe 2022-12 / 第10巻 第6号 / 55…",
        "reserved_order" => 1,
        "reserved_book_id" => "5115282311",
        "reserved_date" => "2023/10/02",
        "state" => BookState::Waiting->value,
        "keep_limit_date" => "",
        "changing_id" => "",
    ];

    protected function setUp(): void
    {
        $this->book = new ReservedBook(
            self::TEST_BOOK["owner"],
            self::TEST_BOOK["title"],
            self::TEST_BOOK["reserved_order"],
            self::TEST_BOOK["reserved_book_id"],
            self::TEST_BOOK["reserved_date"],
            BookState::from(self::TEST_BOOK["state"]),
            self::TEST_BOOK["keep_limit_date"],
            self::TEST_BOOK["changing_id"],
        );
    }

    public function testToArray(): void
    {
        $ary = $this->book->toArray();

        $this->assertEquals(self::TEST_BOOK, $ary);
    }

    public function testFromArray(): void
    {
        $book = ReservedBook::fromArray(self::TEST_BOOK);

        $this->assertEquals($this->book, $book);
    }
}
