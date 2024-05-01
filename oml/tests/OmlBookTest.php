<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use MyApp\common\Utils;
use MyApp\OmlBook;

final class OmlBookTest extends TestCase
{
    private OmlBook $book;

    private const TEST_BOOK = [
        "title" => "ラストで君は「まさか!」と言う ときめきの数字(3分間ノンストップショートストーリー)∥PHP研究所/編∥PHP研究所∥2024.2∥F◇F◇913.68",
    ];

    protected function setUp(): void
    {
        $this->book = new OmlBook(
            self::TEST_BOOK["title"]
        );
    }

    public function testFullTitle()
    {
        $this->assertSame(self::TEST_BOOK["title"], $this->book->fullTitle);
    }

    public function testTitle()
    {
        $this->assertSame("ラストで君は「まさか!」と言う ときめきの数字(3分間ノンストップショートストーリー)", $this->book->title);
    }

    public function testAuthor()
    {
        $this->assertSame("PHP研究所/編", $this->book->author);
    }

    public function testPublishedYear()
    {
        $this->assertSame("2024.2", $this->book->publishedYear);
    }
}
