<?php declare(strict_types=1);

use yananob\MyTools\Utils;
use yananob\MyTools\CacheStore;
use MyApp\Oml;
use MyApp\ReservedBook;
use MyApp\LendingBook;
use MyApp\BookState;

final class OmlTest extends PHPUnit\Framework\TestCase
{
    private Oml $oml;

    private const LOAD_ACCOUNT = "test_01";
    private const SAVE_ACCOUNT = "test_save";

    private const TEST_RESERVED_BOOKS = [
        [
            "keep_limit_date" => "",
            "reserved_book_id" => "5115282311",
            "reserved_order" => 1,
            "reserved_date" => "2023/10/02",
            "state" => BookState::Waiting->value,
            "title" => "[雑誌][巻号] kodomoe 2022-12 ∥ 第10巻 第6号 ∥ 55…",
            "changing_id" => "123456",
        ],
        [
            "keep_limit_date" => "",
            "reserved_book_id" => "5115282312",
            "reserved_order" => 2,
            "reserved_date" => "2023/10/12",
            "state" => BookState::Keeping->value,
            "title" => "[雑誌][巻号] kodomoe 2023-12 ∥ 第10巻 第6号 ∥ 55…",
            "changing_id" => "323456",
        ],
    ];
    private const TEST_LENDING_BOOKS = [
        [
            "lending_book_id" => "6810489705",
            "return_limit_date" => "2023/11/26",
            "state" => BookState::None->value,
            "title" => "ふしぎの時間割(偕成社おたのしみクラブ)∥岡田 淳/作絵∥偕成社∥1998.7∥…",
        ],
        [
            "lending_book_id" => "6810489706",
            "return_limit_date" => "2023/11/30",
            "state" => BookState::Extended->value,
            "title" => "ふしぎの学校∥岡田 淳/作絵∥偕成社∥1998.7∥…",
        ],
    ];

    protected function setUp(): void
    {
        $this->oml = new Oml($is_test=true);
    }

    private function __overwriteOwner(array $books, string $owner): array
    {
        $result = [];
        foreach ($books as $book) {
            $book["owner"] = $owner;
            $result[] = $book;
        }
        return $result;
    }

    private function __arraysToReservedBooks(array $books): array
    {
        $result = [];
        foreach ($books as $book) {
            $result[] = ReservedBook::fromArray($book);
        }
        return Utils::sortObjectArrayByProperty($result, "reservedBookId");
    }

    private function __arraysToLendingBooks(array $books): array
    {
        $result = [];
        foreach ($books as $book) {
            $result[] = LendingBook::fromArray($book);
        }
        return Utils::sortObjectArrayByProperty($result, "lendingBookId");
    }

    public function testGetReservedBooks(): void
    {
        $books = $this->oml->getReservedBooks($account=self::LOAD_ACCOUNT);

        $this->assertEquals(
            $this->__arraysToReservedBooks($this->__overwriteOwner(self::TEST_RESERVED_BOOKS, self::LOAD_ACCOUNT)),
            $books
        );
    }

    public function testGetLendingBooks(): void
    {
        $books = $this->oml->getLendingBooks($account=self::LOAD_ACCOUNT);

        $this->assertEquals(
            $this->__arraysToLendingBooks($this->__overwriteOwner(self::TEST_LENDING_BOOKS, self::LOAD_ACCOUNT)),
            $books
        );
    }

    public function testSaveReservedBooks(): void
    {
        Utils::invokePrivateMethod($this->oml, "__saveReservedBooks", self::SAVE_ACCOUNT, $this->__arraysToReservedBooks($this->__overwriteOwner(self::TEST_RESERVED_BOOKS, self::SAVE_ACCOUNT)));

        $loaded = $this->oml->getReservedBooks($account=self::SAVE_ACCOUNT);

        $this->assertEquals(
            $this->__arraysToReservedBooks($this->__overwriteOwner(self::TEST_RESERVED_BOOKS, self::SAVE_ACCOUNT)),
            $loaded
        );
    }

    public function testUpdateLendingBooks(): void
    {
        Utils::invokePrivateMethod($this->oml, "__saveLendingBooks", self::SAVE_ACCOUNT, $this->__arraysToLendingBooks($this->__overwriteOwner(self::TEST_LENDING_BOOKS, self::SAVE_ACCOUNT)));

        $loaded = $this->oml->getLendingBooks($account=self::SAVE_ACCOUNT);

        $this->assertEquals(
            $this->__arraysToLendingBooks($this->__overwriteOwner(self::TEST_LENDING_BOOKS, self::SAVE_ACCOUNT)),
            $loaded
        );
    }

    public function testAddReservedBookInfo(): void
    {
        $reservedBook = new ReservedBook(self::SAVE_ACCOUNT, "TITLE", 999, "", "", BookState::Waiting, "", "");
        Utils::invokePrivateMethod($this->oml, "__addReservedBookInfo", self::SAVE_ACCOUNT, $reservedBook);

        CacheStore::prune();
        // 追加分が先頭になる模様。ちゃんとソートしたほうがいいかも？
        $loaded = $this->oml->getReservedBooks($account=self::SAVE_ACCOUNT);

        $expected = [];
        $expected[] = $reservedBook;
        $expected = array_merge($expected, $this->__arraysToReservedBooks($this->__overwriteOwner(self::TEST_RESERVED_BOOKS, self::SAVE_ACCOUNT)));

        $this->assertEquals(
            $expected,
            $loaded
        );
    }

    public function testRemoveReservedBookInfo(): void
    {
        $arrayIndex = 1;

        $loaded = $this->oml->getReservedBooks($userId=self::SAVE_ACCOUNT);
        $expected = $loaded;
        unset($expected[$arrayIndex]);
        $expected = array_merge($expected);

        Utils::invokePrivateMethod($this->oml, "__removeReservedBookInfo", self::SAVE_ACCOUNT, $loaded[$arrayIndex]->reservedBookId);
        CacheStore::prune();
        $loaded = $this->oml->getReservedBooks($userId=self::SAVE_ACCOUNT);

        $this->assertEquals(
            $expected,
            $loaded
        );
    }

    public function testUpdateLendingBookInfo(): void
    {
        $arrayIndex = 0;

        $book = LendingBook::fromArray($this->__overwriteOwner(self::TEST_LENDING_BOOKS, self::SAVE_ACCOUNT)[$arrayIndex]);
        $book->fullTitle = "@@@" . $book->fullTitle;
        Utils::invokePrivateMethod($this->oml, "__updateLendingBookInfo", self::SAVE_ACCOUNT, $book);
        CacheStore::prune();
        $loaded = $this->oml->getLendingBooks($account=self::SAVE_ACCOUNT)[$arrayIndex];

        $expected = $this->__arraysToLendingBooks($this->__overwriteOwner(self::TEST_LENDING_BOOKS, self::SAVE_ACCOUNT))[$arrayIndex];
        $expected->fullTitle = "@@@" . $expected->fullTitle;
        $expected->title = "@@@" . $expected->title;
        $this->assertEquals(
            $expected,
            $loaded
        );
    }

    public function testSaveAndGetUpdatedDates(): void
    {
        $reservedBookDate = new DateTime("2023-12-01 01:23:45");
        $this->oml->updateReservedBooksUpdatedDate($reservedBookDate);
        $lendingBookDate = new DateTime("2023-12-11 12:34:56");
        $this->oml->updateLendingBooksUpdatedDate($lendingBookDate);

        $this->assertEquals(
            [
                "reserved_books" => $reservedBookDate,
                "lending_books" => $lendingBookDate,
            ],
            $this->oml->getUpdatedDates(false)
        );
    }
}
