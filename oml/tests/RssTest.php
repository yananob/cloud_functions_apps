<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use MyApp\common\Utils;
use MyApp\ListedBook;
use MyApp\RssType;
use MyApp\Rss;

final class RssTest extends TestCase
{
    protected function setUp(): void
    {
    }

    public function testListBooks_remote(): void
    {
        $rss = new Rss(RssType::UpcomingAdult);
        foreach (["17", "18"] as $category) {
            $books = $rss->listBooks($category);
            $this->assertGreaterThanOrEqual(1, count($books));
        }
    }

    public function testListBooks_local(): void
    {
        $rss = new Rss(null, $filePath = __DIR__ . "/data/rss/upcoming-18.xml");
        $books = $rss->listBooks("18");
        $this->assertSame(135, count($books));
        $this->assertEquals(
            new ListedBook(
                "あのとき死なずにすんだ理由 -あの日、あのとき、あの場所で感じた理解不能な恐怖-∥平山 夢明/監修∥二見書房∥2024.7∥F◇F◇913.68&lt;図書&gt;",
                "0015547946",
            ),
            $books[0]
        );
        $this->assertEquals(
            new ListedBook(
                "隣の席の高嶺の花は、僕の前世の妻らしい。 -今世でも僕のことが大好きだそうです。-(富士見ファンタジア文庫 わ-5-1-1)∥渡 路/著∥KADOKAWA∥2024.6∥Fワタリ◇Fワタリ◇913.6&lt;図書&gt;",
                "0015544252",
            ),
            $books[134]
        );
    }
}
