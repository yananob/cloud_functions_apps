<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use MyApp\common\Utils;

final class UtilsTest extends TestCase
{
    public function testGetConfig(): void
    {
        $config = Utils::getConfig(__DIR__ . "/../config/config_line.json");

        $this->assertArrayHasKey("tokens", $config);
    }

    public static function getBasePathDataProvider(): array
    {
        return [
            // message => [isLocal, appName, expected]
            "local" => [true, "app", "/"],
            "cloud" => [false, "app", "/app/"],
        ];
    }
    /**
     * @dataProvider getBasePathDataProvider
     */
    public function testGetBasePath(bool $isLocal, string $appName, string $expected): void
    {
        $this->assertSame($expected, Utils::getBasePath($isLocal, $appName));
    }

    public static function getBaseUrlDataProvider(): array
    {
        $config = Utils::getConfig(__DIR__ . "/../config/common.json");
        return [
            // message => [isLocal, appName, expected]
            "local" => [true, "app", "http://localhost:8080/"],
            "cloud" => [false, "app", $config["base_url"] . "/app/"],
        ];
    }
    /**
     * @dataProvider getBaseUrlDataProvider
     */
    public function testGetBaseUrl(bool $isLocal, string $appName, string $expected): void
    {
        $this->assertSame($expected, Utils::getBaseUrl($isLocal, $appName));
    }

    private function functionForInvokePrivateMethod(...$params): string
    {
        $result = "";
        foreach ($params as $param) {
            $result .= ".{$param}";
        }
        return $result;
    }

    public function testInvokePrivateMethod(): void
    {
        $params = ["1", "2", "3"];
        $this->assertSame(
            $this->functionForInvokePrivateMethod(...$params),
            Utils::invokePrivateMethod($this, "functionForInvokePrivateMethod", ...$params)
        );
    }

    public function testSortObjectArrayByProperty(): void
    {
        $ary = [];

        $obj2 = new stdClass();
        $obj2->prop = '2';
        $ary[] = $obj2;

        $obj1 = new stdClass();
        $obj1->prop = '1';
        $ary[] = $obj1;

        $obj3 = new stdClass();
        $obj3->prop = '3';
        $ary[] = $obj3;

        $this->assertEquals(
            [$obj1, $obj2, $obj3],
            Utils::sortObjectArrayByProperty($ary, "prop")
        );
    }
}
