<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use MyApp\common\Utils;
use MyApp\BookState;
use MyApp\LendingBook;

final class LendingBookTest extends TestCase
{
    private LendingBook $book;

    private const TEST_BOOK = [
        "owner" => "TEST_OWNER",
        "title" => "ふしぎの時間割(偕成社おたのしみクラブ)∥岡田 淳/作絵∥偕成社∥1998.7∥…",
        "return_limit_date" => "2023/11/26",
        "lending_book_id" => "6810489705",
        "state" => BookState::None->value,
    ];

    protected function setUp(): void
    {
        $this->book = new LendingBook(
            self::TEST_BOOK["owner"],
            self::TEST_BOOK["title"],
            self::TEST_BOOK["return_limit_date"],
            BookState::from(self::TEST_BOOK["state"]),
            self::TEST_BOOK["lending_book_id"],
        );
    }

    public function testToArray(): void
    {
        $ary = $this->book->toArray();

        $this->assertEquals(self::TEST_BOOK, $ary);
    }

    public function testFromArray(): void
    {
        $book = LendingBook::fromArray(self::TEST_BOOK);

        $this->assertEquals($this->book, $book);
    }

    public static function providerIsReturndateCame(): array
    {
        return [
            // case => today, expected
            "return date didn't come" => ["2023/11/25", false],
            "return date came" => ["2023/11/26", true],
        ];
    }
    #[DataProvider('providerIsReturndateCame')]
    public function testIsReturndateCame(string $today, bool $expected): void
    {
        $this->book->setDebugDate($today);

        $this->assertSame($expected, $this->book->isReturndateCame());
    }

    public static function providerIsExtendable(): array
    {
        return [
            // case => BookState, expected
            "state = none" => [BookState::None, true],
            "state = extended" => [BookState::Extended, false],
            "state = overdue" => [BookState::Overdue, false],
            "state = reserved" => [BookState::Reserved, false],
        ];
    }
    #[DataProvider('providerIsExtendable')]
    public function testIsExtendable(BookState $bookState, bool $expected): void
    {
        $this->book->state = $bookState;

        $this->assertSame($expected, $this->book->isExtendable());
    }
}
