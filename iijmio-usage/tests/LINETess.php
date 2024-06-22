<?php declare(strict_types=1);

final class LINETest extends PHPUnit\Framework\TestCase
{
    public function testSendMessage(): void
    {
        $line = new \yananob\mytools\Line(__DIR__ . '/config.json.test');

        $line->sendMessage("nobu", "[LINETest] hoge\nhoge!");

        $this->assertTrue(true);
    }
}
