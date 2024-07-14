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
        $rss = new Rss(RssType::Upcoming);
        foreach ([17, 18] as $lv2) {
            $books = $rss->listBooks($lv2);
            $this->assertGreaterThanOrEqual(1, count($books));
        }
    }

    public function testListBooks_local(): void
    {
        $rss = new Rss(null, $filePath = __DIR__ . "/data/rss/lv2-18.xml");
        $books = $rss->listBooks(18);
        $this->assertSame(135, count($books));
        $this->assertEquals(
            new ListedBook(
                "合作探偵小説コレクション 7 むかで横丁/ジュピター殺人事件",
                "0015547923",
            ),
            $books[1]
        );
        $this->assertEquals(
            new ListedBook(
                "隣の席の高嶺の花は、僕の前世の妻らしい。 -今世でも僕のことが大好きだそうです。-(富士見ファンタジア文庫 わ-5-1-1)",
                "0015544252",
            ),
            $books[134]
        );
    }
}
