<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use MyApp\common\Utils;
use MyApp\common\CacheStore;

final class CacheStoreTest extends TestCase
{
    // public static function isLaunchDataProvider(): array
    // {
    //     return [
    //         // message => [now, timing_weekdays, timing_day, timing_hour, expected]
    //         "Match: weekdays + hour" => ["2023-09-21 10:00:00", ["Wed", "Thu"], null, 10, true],
    //         "Match: day + hour" => ["2023-02-01 10:00:00", null, 1, 10, true],
    //         "Match: hour" => ["2023-02-01 10:00:00", null, null, 10, true],
    //         "Unmatch: weekdays + hour" => ["2023-09-21 10:00:00", ["Mon", "Tue"], null, 10, false],
    //         "Unmatch: day + hour" => ["2023-02-01 10:00:00", null, 2, 10, false],
    //         "Unmatch: hour" => ["2023-02-01 10:00:00", null, null, 11, false],
    //     ];
    // }
    /**
     * @ dataProvider isLaunchDataProvider
     */
    public function testPutAndGetAndClear(): void
    {
        CacheStore::prune();

        $checkKey = "test_key";
        $this->assertEmpty(CacheStore::get($checkKey));

        $data = [1, 2, 3];
        CacheStore::put($checkKey, $data);
        $this->assertEquals($data, CacheStore::get($checkKey));

        CacheStore::clear($checkKey);
        $this->assertEmpty(CacheStore::get($checkKey));
    }
}
