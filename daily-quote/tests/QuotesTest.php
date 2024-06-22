<?php declare(strict_types=1);

use MyApp\Quotes;

final class QuotesTest extends PHPUnit\Framework\TestCase
{
    private $quotes;
    private array $check_fields = ["no", "message", "author", "source", "source_link"];

    protected function setUp(): void
    {
        $this->quotes = new Quotes($collection_name="daily-quotes-test");
    }

    private function __filterCheckFields(array $input): array
    {
        $result = [];
        foreach ($input as $key => $value) {
            if (in_array($key, $this->check_fields)) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    public function testGet(): void
    {
        $index = 1;
        $quote = $this->quotes->get($index);

        $this->assertEquals(
            [
                "no" => $index,
                "message" => "設計とプログラミングをやるんは人間やで。それを忘れたらあかん。忘れたら、何もかも無くしてまうで。",
                "author" => "Bjarne Stroustrup",
                "source" => "XPエピソード　関西弁バージョン",
                "source_link" => "http://agileware.jp/articles/xp/xpepisode-kansai.html",
            ],
            $this->__filterCheckFields($quote)
        );
    }

    public function testGetRandom(): void
    {
        $quote = $this->quotes->getRandom();

        $this->assertNotEmpty($quote);
        foreach (["no", "message", "author", "source", "source_link"] as $key) {
            $this->assertArrayHasKey($key, $quote);
        }
        foreach (["no", "message", "author", "source"] as $key => $value) {
            $this->assertNotEmpty($value);
        }
    }

    public function testBlank(): void
    {
        $blank = $this->quotes->blank();

        $check = [];
        foreach ($this->check_fields as $key) {
            $check[$key] = "";
        }

        $this->assertSame($check, $blank);
    }

    public function testAddWithNo(): void
    {
        $count = $this->quotes->count();
        $index = $this->quotes->maxNo() + 1;
        // print_r("count: {$count}");

        $save_doc = [
            "no" => $index,
            "message" => "message add with no {$index}",
            "author" => "author add {$index}",
            "source" => "source add {$index}",
            "source_link" => "source_link add {$index}",
        ];
        $this->quotes->add($save_doc, $index);

        $this->assertEquals($save_doc, $this->__filterCheckFields($this->quotes->get($index)));
        $this->assertSame($count + 1, $this->quotes->count());
    }

    public function testAddWithoutNo(): void
    {
        $count = $this->quotes->count();
        $index = $this->quotes->maxNo() + 1;
        // print_r("index: {$index}");

        $save_doc = [
            "message" => "message add without no {$index}",
            "author" => "author add {$index}",
            "source" => "source add {$index}",
            "source_link" => "source_link add {$index}",
        ];
        $this->quotes->add($save_doc);

        $save_doc["no"] = $index;
        $this->assertEquals($save_doc, $this->__filterCheckFields($this->quotes->get($index)));
        $this->assertSame($count + 1, $this->quotes->count());
    }

    public function testUpdate(): void
    {
        $index = $this->quotes->maxNo();

        $save_doc = [
            "no" => $index,
            "message" => "message update {$index}",
            "author" => "author update {$index}",
            "source" => "source update {$index}",
            "source_link" => "source_link update {$index}",
        ];
        $this->quotes->update($index, $save_doc);

        $this->assertEquals($save_doc, $this->__filterCheckFields($this->quotes->get($index)));
    }

    public function testRemove(): void
    {
        $count = $this->quotes->count();

        $index = $this->quotes->maxNo();
        $this->quotes->remove($index);

        $this->assertSame($count - 1, $this->quotes->count());
    }
}
