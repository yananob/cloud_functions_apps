<?php declare(strict_types=1);

// use PHPUnit\Framework\Attributes\DataProvider;
// use PHPUnit\Framework\TestCase;
use yananob\MyTools\Utils;
use MyApp\BookState;
use MyApp\ReservedBook;
use MyApp\LendingBook;
use MyApp\ListedBook;
use MyApp\Crawler;

final class CrawlerTest extends PHPUnit\Framework\TestCase
{
    private Crawler $crawler;

    private string $test_user_id;
    private string $test_password;

    protected function setUp(): void
    {
        $config = Utils::getConfig(__DIR__ . "/configs/accounts.json");
        $this->test_user_id = $config["test_users"][0]["userid"];
        $this->test_password = $config["test_users"][0]["password"];
        $this->crawler = new Crawler(
            $userid=$this->test_user_id, $password=$this->test_password
        );
    }

    private function __loadTestHtml(string $filename): string
    {
        return file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . $filename);
    }

    public function testLogin(): void
    {
        [$_, $res_body] = Utils::invokePrivateMethod($this->crawler, "__login");
        $this->assertStringContainsString("あなたの図書館利用状況は以下の通りです。", strval($res_body));
        $this->assertStringContainsString("メニューへ", strval($res_body));
        $this->assertStringContainsString("ログアウト", strval($res_body));
    }

    public function testParseReservedBooksPage_withBooks(): void
    {
        $contents = $this->__loadTestHtml("reserved-list.html");
        $books = Utils::invokePrivateMethod($this->crawler, "__parseReservedBooksPage", $contents);

        $this->assertCount(12, $books);

        $this->assertEquals(new ReservedBook(
            $owner=$this->test_user_id,
            $title="うえをむいてあるこう -ジャイアント馬場、世界をわかせた最初のショーヘイ-∥くす…",
            $reservedOrder=4,
            $reservedBookId="0015483733",
            $reservedDate="2024/03/01",
            $state=BookState::Waiting,
            $keepLimitDate="",
            $changingId="00117628",
        ), $books[2]);
    }

    public function testParseReservedBooksPage_withoutBooks(): void
    {
        $contents = $this->__loadTestHtml("status_with-lending-reserved.html");
        $books = Utils::invokePrivateMethod($this->crawler, "__parseReservedBooksPage", $contents);
        $this->assertEmpty($books);
    }

    public function testParseLendingBooksPage_withBooks(): void
    {
        $contents = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "lending-list.html");
        $books = Utils::invokePrivateMethod($this->crawler, "__parseLendingBooksPage", $contents);

        $this->assertCount(9, $books);

        $this->assertEquals(new LendingBook(
            $owner=$this->test_user_id,
            $title="死体を買う男(講談社文庫)∥歌野 晶午/[著]∥講談社∥2001.11∥Fウタノ…",
            $return_limit_date="2024/03/16",
            $state=BookState::None,
            $lending_book_id="5340717296",
        ), $books[0]);
        $this->assertEquals(new LendingBook(
            $owner=$this->test_user_id,
            $title="RUN+TRAIL vol.2 トレイルランレースをはじめよう ハセツネ/UTM…",
            $return_limit_date="2024/03/23",
            $state=BookState::Extended,
            $lending_book_id="1023017013",
        ), $books[1]);
    }

    public function testParseLendingBooksPage_withoutBooks(): void
    {
        $contents = $this->__loadTestHtml("status_with-lending-reserved.html");
        $books = Utils::invokePrivateMethod($this->crawler, "__parseLendingBooksPage", $contents);
        $this->assertEmpty($books);
    }

    public function testCrawlReservedBooks(): void
    {
        $books = $this->crawler->crawlReservedBooks();
        $this->assertIsArray($books);
        // if (count($books) > 0) {
        //     $this->assertIsObject($books[0]);
        // }
    }

    public function testCrawlLendingBooks(): void
    {
        $books = $this->crawler->crawlLendingBooks();
        $this->assertIsArray($books);
        // if (count($books) > 0) {
        //     $this->assertIsObject($books[0]);
        // }
    }

    public function testParseLendingExtendPage(): void
    {
        $expected = [
            "org.apache.struts.taglib.html.TOKEN" => "7f5f95734ae9547612ad1f75906c7dd0",
            "sortkey" => "lmtdt,id/ASC",
            "startpos" => "1",
            "lenidlist" => "551161914",
        ];
        $contents = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "lending-extend-confirm.html");
        $params = Utils::invokePrivateMethod($this->crawler, "__parseLendingExtendPage", $contents);
        $this->assertSame($expected, $params);
    }

    public function testParseLendingExtendResultPage(): void
    {
        $expected = new LendingBook(
            $owner=$this->test_user_id,
            $title="ふしぎ駄菓子屋銭天堂 18∥廣嶋 玲子/作∥偕成社∥2022.9∥Fヒロシ◇Fヒ…",
            $returnLimitDate="2024/02/04",
            $state=BookState::Extended,
            $lendingBookId="5511619149",
        );
        $contents = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "lending-extended-result.html");
        $params = Utils::invokePrivateMethod($this->crawler, "__parseLendingExtendResultPage", $contents);
        $this->assertEquals($expected, $params);
    }

    public static function providerSearch(): array
    {
        return [
            // case => keyword, title, author, page, expected (only 1st & 5th books)
            "キーワード＝ミルキー杉山, page=1" => ["ミルキー杉山", "", "", 1, [
                new ListedBook("もしかしたら名探偵∥杉山 亮/作∥偕成社∥1992.3∥Fスキヤ◇Fスキヤ◇913.6", "0000261123"),
                new ListedBook("そんなわけで名探偵∥杉山 亮/作∥偕成社∥1998.4∥Fスキヤ◇Fスキヤ◇913.6", "0000670401"),
            ]],
            "キーワード＝ミルキー杉山, page=2" => ["ミルキー杉山", "", "", 2, [
                new ListedBook("なんだかんだ名探偵∥杉山 亮/作∥偕成社∥1999.3∥Fスキヤ◇Fスキヤ◇913.6", "0000727189"),
                new ListedBook("あめあがりの名探偵([ミルキー杉山のあなたも名探偵シリーズ] [9])∥杉山 亮/作∥偕成社∥2005.12∥Fスキヤ◇Fスキヤ◇913.6", "0011103578"),
            ]],
            "著者＝宮部みゆき, page=1" => ["", "", "宮部みゆき", 1, [
                new ListedBook("推理小説代表作選集 -推理小説年鑑- 1988∥日本推理作家協会/編∥講談社∥1988.5∥F◇F◇913.68", "0070002503"),
                new ListedBook("東京(ウォーター・フロント)殺人暮色(カッパ・ノベルス)∥宮部 みゆき/著∥光文社∥1990.4∥Fミヤヘ◇Fミヤヘ◇913.6", "0000142263"),
            ]],
            "タイトル＝ガリレオ, 著者＝東野圭吾, page=1" => ["", "ガリレオ", "東野圭吾", 1, [
                new ListedBook("探偵ガリレオ([ガリレオ] [1])∥東野 圭吾/著∥文芸春秋∥1998.5∥Fヒカシ◇Fヒカシ◇913.6", "0000677804"),
                new ListedBook("容疑者Xの献身([ガリレオ] [3])∥東野 圭吾/著∥文藝春秋∥2005.8∥Fヒカシ◇Fヒカシ◇913.6", "0011043507"),
            ]],
        ];
    }
    #[PHPUnit\Framework\Attributes\DataProvider('providerSearch')]
    public function testSearch($keyword, $title, $author, $page, $expected): void
    {
        $searchedBooks = $this->crawler->search($keyword, $title, $author, $page, $order="asc");
        $this->assertGreaterThanOrEqual(count($searchedBooks), 5);
        $this->assertEquals($expected[0], $searchedBooks[0]);
        $this->assertEquals($expected[1], $searchedBooks[4]);
    }

    public function testParseSearchResultPage(): void
    {
        $expected = [
            new ListedBook("うえをむいて名探偵([ミルキー杉山のあなたも名探偵シリーズ] [25])∥杉山 亮/作∥偕成社∥2023.5∥Fスキヤ◇Fスキヤ◇913.6", "0015365135"),
            [],
            [],
            [],
            new ListedBook("まちがいなく名探偵([ミルキー杉山のあなたも名探偵シリーズ] [22])∥杉山 亮/作∥偕成社∥2020.5∥Fスキヤ◇Fスキヤ◇913.6", "0014859250"),
        ];
        $contents = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "search-result.html");
        $searchedBooks = Utils::invokePrivateMethod($this->crawler, "__parseSearchResultPage", $contents);
        $this->assertSame(count($searchedBooks), 5);
        $this->assertEquals($expected[0], $searchedBooks[0]);
        $this->assertEquals($expected[4], $searchedBooks[4]);
    }

    // // MEMO: 普段有効にしてしまうと、重複予約できないエラーになるはず
    // 　→　予約キャンセルサポートしたら、行ける　→　でも予約がフルだとNG
    // public function testReserveAndCancel(): void
    // {
    //     // 予約前の予約図書一覧取得

    //     // 予約
    //     $this->crawler->reserve("0014859250");
    //     // $this->asserTrue(true);

    //     // 予約前＋予約図書になってることを確認
    // }

    public static function providerCheckReserveConfirmPage(): array
    {
        return [
            // case => filename, expected exception message
            "success" => ["reserve-confirm_success.html", ""],
            "fail" => ["reserve-confirm_fail.html", "二重依頼または本人貸出中"],
        ];
    }
    #[PHPUnit\Framework\Attributes\DataProvider('providerCheckReserveConfirmPage')]
    public function testCheckReserveConfirmPage($filename, $expectedMessage): void
    {
        $contents = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . $filename);
        if (empty($expectedMessage)) {
            $this->assertTrue(true);
        }
        else {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage($expectedMessage);
        }
        Utils::invokePrivateMethod($this->crawler, "__checkReserveConfirmPage", $contents);
    }

    // getBookReserveInfo: 予約数とかが不定になるため、実行のみ
    public function testGetBookReserveInfo(): void
    {
        $info = $this->crawler->getBookReserveInfo("0015485620");
        $this->assertNotEmpty($info["reserves"]);
        $this->assertNotEmpty($info["waitWeeks"]);
    }

    public static function providerParseBookDetailPage(): array
    {
        return [
            // case => filename, book id, title, author, published by, published year, expected books, expected reserves, expected waitWeeks
            "some reserves" => [
                "book-detail_some-reserves.html",
                "0015365135", "うえをむいて名探偵", "杉山 亮/作<br/>中川 大輔/絵", "東京：偕成社", "2023.5",
                3, 25, 18,
            ],
            "no reserves" => [
                "book-detail_no-reserves.html",
                "0012200573", "少年少女世界名作ライブラリー　16", "", "東京：山田書院", "[19--]",
                1, 0, 0,
            ],
        ];
    }
    #[PHPUnit\Framework\Attributes\DataProvider('providerParseBookDetailPage')]
    public function testParseBookDetailPage($filename, $expectedBookId, $expectedTitle, $expectedAuthor, $expectedPublishedBy, $expectedPublishedYear, $expectedBooks, $expectedReserves, $expectedWaitWeeks): void
    {
        $contents = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . $filename);
        $bookDetail = Utils::invokePrivateMethod($this->crawler, "__parseBookDetailPage", $contents);
        $this->assertSame($expectedBookId, $bookDetail["book_id"]);
        $this->assertSame($expectedTitle, $bookDetail["title"]);
        $this->assertSame($expectedAuthor, $bookDetail["author"]);
        $this->assertSame($expectedPublishedBy, $bookDetail["published_by"]);
        $this->assertSame($expectedPublishedYear, $bookDetail["published_year"]);
        $this->assertSame($expectedBooks, $bookDetail["books"]);
        $this->assertSame($expectedReserves, $bookDetail["reserves"]);
        $this->assertSame($expectedWaitWeeks, $bookDetail["waitWeeks"]);
    }

    // public function testGetBookContent(): void
    // {
    //     // https://web.oml.city.osaka.lg.jp/webopac/mobcatdbl.do?cmd=bibcte&bibid=0015485620&currentpos=5
    //     $info = $this->crawler->getBookContent("0015485620");
    //     $expected = "数々のサバイバルを乗り越えてきたジオたちと一緒にクイズを解きながら、危険生物について学ぼう。「ヒグマから安全ににげる方法は?」「ヒアリにさされるとどうなる?」など、40問の危険生物クイズと解説を収録する。<br/>世界中の危険(きけん)生物を集めた動物園にとじこめられてしまったジオたち。クイズに答えて、無事動物園から出られるか!?「アフリカゾウが敵(てき)をいかくするときのポーズは?」「アカエイの毒(どく)とげはどこにある?」など、危険生物に関するクイズ40問と、わかりやすい説明がのっています。<br/>";
    //     $this->assertSame($expected, $info["content"]);
    // }

    // https://web.oml.city.osaka.lg.jp/webopac/mobrsvlst.do?cmddel=cmddel&svcidlist=001431030&startpos=1&hitcnt=12&listcnt=3&sortkey=rsvst,canrs,reqdt/DESC

    public function testParseReservationCancelInputPage(): void
    {
        $contents = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "reserve-cancel-input.html");
        $pageDetail = Utils::invokePrivateMethod($this->crawler, "__parseCancelReservationInputPage", $contents);
        //   "body": "org.apache.struts.taglib.html.TOKEN=268995cb27ec7fcc1b999968b058a5e0&startpos=1&listpos=&hitcnt=1&listcnt=3&sortkey=rsvst%2Ccanrs%2Creqdt%2FDESC&svcidlist=00117543",

        $this->assertSame([
            "org.apache.struts.taglib.html.TOKEN" => "268995cb27ec7fcc1b999968b058a5e0",
            "startpos" => "1",
            "listpos" => "",
            "hitcnt" => "1",
            "listcnt" => "3",
            "sortkey" => "rsvst,canrs,reqdt/DESC",
            "svcidlist" => "00117543",
        ], $pageDetail);
    }

    public function testParseReserveResultPage(): void
    {
        $contents = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "reserve-result.html");
        $bookInfo = Utils::invokePrivateMethod($this->crawler, "__parseReserveResultPage", $contents);

        $this->assertSame([
            "title" => "家政夫くんは名探偵! [4] 夏休みの料理と推理(ファン文庫 く-2-6)∥楠谷 佑/著∥マイナビ出…",
        ], $bookInfo);
    }
}
