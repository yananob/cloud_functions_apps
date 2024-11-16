<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use MyApp\common\Utils;
use MyApp\ReservedBook;
use MyApp\LendingBook;
use MyApp\AlertType;
use MyApp\BookState;
use MyApp\Alerter;

final class AlerterTest extends TestCase
{
    private Alerter $alerter;

    protected function setUp(): void
    {
        $config = [
            "line_bot" => "nobu",
            "line_target" => "nobu",
        ];
        $this->alerter = new Alerter($config, "");
    }

    public static function providerAddAlert(): array
    {
        return [
            // case => type, title, info, messages
            "duplicated reservations" => [AlertType::DuplicatedReserved, "TITLE_A", "後予約@01", "【予約の重複】TITLE_A(後予約@01)"],
            "duplicated among reservation and lending" => [AlertType::DuplicatedReservedAndLending, "TITLE_B", "後予約@02", "【予約と貸出の重複】TITLE_B(後予約@02)"],
            "return limit" => [AlertType::ReturnLimit, "TITLE_C", "", "【要返却】TITLE_C"],
            "keep limit" => [AlertType::KeepLimit, "TITLE_D", "", "【要受取】TITLE_D"],
            "extended" => [AlertType::AutoExtended, "TITLE_E", "", "【自動延長済】TITLE_E"],
        ];
    }
    #[DataProvider('providerAddAlert')]
    public function testAddAlert(AlertType $type, string $title, string $info, string $expected): void
    {
        $this->alerter->addAlert($type, $title, $info);
        $this->assertSame([$expected], $this->alerter->getMessages());
    }

    public static function providerCheckDuplicates(): array
    {
        return [
            "duplicated among reserved books" => [
                "reserved_books" => [
                    // owner, title, reserved_order
                    ["OWNER_01", "TITLE_A", 16],
                    ["OWNER_02", "TITLE_A", 3],
                    ["OWNER_02", "TITLE_B", 1],
                    ["OWNER_03", "TITLE_B", ""],
                ],
                "lending_books" => [],
                "alerts" => [
                    "【予約の重複】TITLE_A(後予約@01)",
                    "【予約の重複】TITLE_B(後予約@02)",
                ],
            ],
            "duplicated among reserved and lending books" => [
                "reserved_books" => [
                    // owner, title, reserved_order
                    ["OWNER_01", "TITLE_A", 1],
                    ["OWNER_02", "TITLE_C", 1],
                ],
                "lending_books" => [
                    // owner, title
                    ["OWNER_01", "TITLE_A"],
                    ["OWNER_02", "TITLE_B"],
                ],
                "alerts" => [
                    "【予約と貸出の重複】TITLE_A(予約@01)",
                ],
            ],
        ];
    }
    #[DataProvider('providerCheckDuplicates')]
    public function testCheckDuplicates($reserved_books, $lending_books, $alerts): void
    {
        $check_reserved_books = [];
        foreach ($reserved_books as $reserved_book) {
            $check_reserved_books[] = new ReservedBook($reserved_book[0], $reserved_book[1], intval($reserved_book[2]), "", "", BookState::None, "", "");
        }
        $check_lending_books = [];
        foreach ($lending_books as $lending_book) {
            $check_lending_books[] = new LendingBook($lending_book[0], $lending_book[1], "", BookState::None, "");
        }

        $this->alerter->checkDuplicates($check_reserved_books, $check_lending_books);
        $this->assertEquals($alerts, $this->alerter->getMessages());
    }

    public static function providerCheckReturnLimitDate(): array
    {
        return [
            // case => title, state, return limit date, alerts
            "limit=today" => ["TITLE_A", BookState::None, "2023-07-15", []],
            "limit=2 days later" => ["TITLE_B", BookState::None, "2023-07-17", []],
            "limit=3 days later" => ["TITLE_C", BookState::None, "2023-07-18", []],
            "limit=2 days later, extended" => ["TITLE_D", BookState::Extended, "2023-07-17", ["【要返却】TITLE_D"]],
            "limit=3 days later, extended" => ["TITLE_D2", BookState::Extended, "2023-07-18", []],
            "limit=2 days later, reserved" => ["TITLE_E", BookState::Reserved, "2023-07-17", ["【要返却】TITLE_E"]],
            "limit=3 days later, reserved" => ["TITLE_E2", BookState::Reserved, "2023-07-18", []],
            "limit=2 days later, overdue" => ["TITLE_F", BookState::Overdue, "2023-07-17", ["【要返却】TITLE_F"]],
            "limit=3 days later, overdue" => ["TITLE_F2", BookState::Overdue, "2023-07-18", []],
        ];
    }
    #[DataProvider('providerCheckReturnLimitDate')]
    public function testCheckReturnLimitDate(string $title, BookState $state, string $return_limit_date, array $expected): void
    {
        $this->alerter->setDebugDate("2023-07-15");

        $book = new LendingBook("", $title, $return_limit_date, $state, "");
        $this->alerter->checkReturnLimitDate([$book]);

        $this->assertSame($expected, $this->alerter->getMessages());
    }

    public static function providerCheckKeepLimitDate(): array
    {
        return [
            // case => title, state, keep limit date, alerts
            "state=''" => ["TITLE_A2", BookState::None, "", []],
            "limit=today, keeping" => ["TITLE_A", BookState::Keeping, "2023-07-15", ["【要受取】TITLE_A"]],
            "limit=2 days later, keeping" => ["TITLE_C", BookState::Keeping, "2023-07-17", ["【要受取】TITLE_C"]],
            "limit=3 days later, keeping" => ["TITLE_A", BookState::Keeping, "2023-07-18", []],
            "limit=2 days later,state=expired" => ["TITLE_D", BookState::Expired, "2023-07-17", ["【要受取】TITLE_D"]],
            "limit=3 days later,state=expired" => ["TITLE_D2", BookState::Expired, "2023-07-18", []],
        ];
    }
    #[DataProvider('providerCheckKeepLimitDate')]
    public function testCheckKeepLimitDate(string $title, BookState $state, string $keep_limit_date, array $expected): void
    {
        $this->alerter->setDebugDate("2023-07-15");

        $book = new ReservedBook("", $title, 0, "", "2000/1/1", $state, $keep_limit_date, "");
        $this->alerter->checkKeepLimitDate([$book]);

        $this->assertSame($expected, $this->alerter->getMessages());
    }
}
