<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

final class LINETest extends PHPUnit\Framework\TestCase
{
    public function testSendMessage(): void
    {
        $line = new \yananob\mytools\Line(__DIR__ . '/tests/config.json.test');

        $line->sendMessage("nobu", "[LINETest] hoge\nhoge!");

        $this->assertTrue(true);
    }
}
