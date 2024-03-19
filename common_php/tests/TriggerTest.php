<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use MyApp\common\Utils;
use MyApp\common\Trigger;

final class TriggerTest extends TestCase
{
    public static function isLaunchDataProvider(): array
    {
        return [
            // message => [now, timing_weekdays, timing_day, timing_hour, expected]
            "Match: weekdays + hour" => ["2023-09-21 10:00:00", ["Wed", "Thu"], null, 10, true],
            "Match: day + hour" => ["2023-02-01 10:00:00", null, 1, 10, true],
            "Match: hour" => ["2023-02-01 10:00:00", null, null, 10, true],
            "Unmatch: weekdays + hour" => ["2023-09-21 10:00:00", ["Mon", "Tue"], null, 10, false],
            "Unmatch: day + hour" => ["2023-02-01 10:00:00", null, 2, 10, false],
            "Unmatch: hour" => ["2023-02-01 10:00:00", null, null, 11, false],
        ];
    }
    /**
     * @dataProvider isLaunchDataProvider
     */
    public function testIsLaunch($now, $timing_weekdays, $timing_day, $timing_hour, $expected): void
    {
        $timing = [];
        if (!is_null($timing_weekdays)) {
            $timing["weekdays"] = $timing_weekdays;
        }
        if (!is_null($timing_day)) {
            $timing["day"] = $timing_day;
        }
        if (!is_null($timing_hour)) {
            $timing["hour"] = $timing_hour;
        }
        $trigger = new Trigger();
        $trigger->setDebugDate($now);
        $this->assertEquals($trigger->isLaunch($timing), $expected);
    }

    public static function isWatchTimingDataProvider(): array
    {
        return [
            // message => [now, expected]
            "True: 06:30" => ["2023-09-21 06:30:00", true],
            "True: 23:30" => ["2023-02-01 23:30:00", true],
            "False: 06:29" => ["2023-02-01 06:29:00", false],
            "False: 23:31" => ["2023-02-01 23:31:00", false],
        ];
    }
    /**
     * @dataProvider isWatchTimingDataProvider
     */
    public function testIsWatchTiming($now, $expected): void
    {
        $trigger = new Trigger();
        $trigger->setDebugDate($now);
        $this->assertEquals($trigger->isWatchTiming(), $expected);
    }
}
