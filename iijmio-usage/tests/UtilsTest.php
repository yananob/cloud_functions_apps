<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use MyApp\common\Utils;

final class UtilsTest extends TestCase
{
    public function testGetConfig(): void
    {
        $config = Utils::getConfig(dirname(__FILE__) . "/config.json.test");
        $this->assertEquals("value1", $config["key1"]);
        $this->assertEquals("value2-1", $config["key2"]["key2-1"]);
    }
}
