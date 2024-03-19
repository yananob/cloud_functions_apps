<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use MyApp\common\LINE;
use MyApp\common\Utils;

final class LINETest extends TestCase
{
    public function testSendMessage(): void
    {
        $line = new LINE();

        $line->sendMessage("nobu", "[LINETest] hoge\nhoge!");

        $this->assertTrue(true);
    }
}
