<?php declare(strict_types=1);

namespace MyApp;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
// use GuzzleHttp\HandlerStack;
// use GuzzleHttp\Handler\CurlHandler;
// use GuzzleHttp\Handler\MockHandler;
// use GuzzleHttp\Exception\ConnectException;
// use GuzzleHttp\Exception\RequestException;
// use GuzzleHttp\Middleware;

use MyApp\common\Logger;
use MyApp\BookState;
use MyApp\ReservedBook;
use MyApp\Oml;

class Crawler
{
    private Client $client;
    private CookieJar $cookieJar;

    public function __construct(private string $userId, private string $password)
    {
        // $decider = function ($retries, $request, $response, $exception) {
        //     if ($retries < 2) {    // Sends 3 requests totally including the first request
        //         return true;    // retry
        //     }
        //     if ($exception instanceof ConnectException || $exception instanceof RequestException) {
        //         return true;    // retry
        //     }
        //     return false;   // give up
        // };
        // $delay = function ($retries) {
        //     return 1 * 1000;        // unit: ms
        // };
        // $retry = Middleware::retry($decider, $delay);
        // $stack = HandlerStack::create(new CurlHandler());
        // // $stack = HandlerStack::create($mockHandler);     // for testing
        // $stack->push($retry);
        $this->client = new Client([
            'base_uri' => 'https://web.oml.city.osaka.lg.jp',
            'timeout'  => 10.0,
            // 'handler' => $stack,     // 予約の登録時に二重予約になるみたいなので、無効化
        ]);
        $this->cookieJar = new CookieJar;
    }

    private function __checkResponse($response): void
    {
        if (!in_array($response->getStatusCode(), [200])) {
            throw new \Exception("Request error. [" . $response->getStatusCode() . "] " . $response->getReasonPhrase());
        }
    }

    private function __getHeaders(): array
    {
        return [
            // これを与えないと、HTMLが結構変わったり、検索時の書籍名がより短い（モバイル向け？）ものになる
            "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36",
        ];
    }

    private function __login(): array
    {
        $response = $this->client->request(
            "get", "/webopac/mobidf.do?cmd=init&next=mobasklst", ["cookies" => $this->cookieJar]
        );
        $this->__checkResponse($response);

        $response = $this->client->request(
            "post", "/webopac/mobidf.do?cmd=login", [
                "cookies" => $this->cookieJar,
                "form_params" => [
                    "userid" => $this->userId,
                    "password" => $this->password,
                ]
            ]
        );
        $this->__checkResponse($response);
        // $this->logger->log("login done");
        return [$response->getStatusCode(), $response->getBody()];
    }

    private function __isStatusPage(string $contents): bool
    {
        return str_contains($contents, '- 利用状況一覧 -');
    }

    public function crawlReservedBooks(): array
    {
        $this->__login();

        $response = $this->client->request(
            "get", "/webopac/mobrsvlst.do?listcnt=" . Oml::MAX_RESERVABLE_COUNT, [
                "headers" => $this->__getHeaders(),
                "cookies" => $this->cookieJar
            ]
        );
        $this->__checkResponse($response);

        return $this->__parseReservedBooksPage((string)$response->getBody());
    }

    private function __parseReservedBooksPage(string $contents): array
    {
        if ($this->__isStatusPage($contents)) {
            return [];
        }

        $contents = preg_replace('/[\S\s]+<form name="askrsvform"/m', "", $contents);

        $result = [];
        $contentsBooks = explode('<hr/>', $contents);
        foreach ($contentsBooks as $contentsBook) {
            preg_match("@<a href=\"mobrsvlst.do\?cmddel=cmddel&svcidlist=([0-9]+?)&startpos=@", $contentsBook, $matches);
            if (!$matches || count($matches) < 2) {
                throw new \Exception("Could not get changingId: " . $contentsBook);
            }
            $changingId = $matches[1];

            $book = new ReservedBook(
                $owner=$this->userId,
                $title=$this->__extractBookPartText($contentsBook, "資料名"),
                $reservedOrder=intval($this->__extractBookPartText($contentsBook, "予約順")),
                $reservedBookId=$this->__extractBookPartText($contentsBook, "書誌ID"),
                $reservedDate=$this->__extractBookPartText($contentsBook, "予約受付日"),
                $state=BookState::from($this->__extractBookPartText($contentsBook, "状態")),
                $keepLimitDate=$this->__extractBookPartText($contentsBook, "取置期限日"),
                $changingId=$changingId
            );
            if (empty($book->reservedBookId) || empty($book->title) || empty($book->reservedDate)) {
                continue;
            }
            $result[] = $book;
        }

        return $result;
    }

    public function crawlLendingBooks(): array
    {
        $this->__login();

        $response = $this->client->request(
            "get", "/webopac/moblenlst.do?listcnt=" .  Oml::MAX_LENDABLE_COUNT, [
                "headers" => $this->__getHeaders(),
                "cookies" => $this->cookieJar
            ]
        );
        $this->__checkResponse($response);

        return $this->__parseLendingBooksPage((string)$response->getBody());
    }

    private function __parseLendingBooksPage(string $contents): array
    {
        if ($this->__isStatusPage($contents)) {
            return [];
        }

        $contents = preg_replace('/.+<form name="asklenform"/', "", $contents);

        $result = [];
        $contentsBooks = explode('<hr style="width:100%"/>', $contents);
        foreach ($contentsBooks as $contentsBook) {
            $book = new LendingBook(
                $owner=$this->userId,
                $title=$this->__extractBookPartText($contentsBook, "資料名"),
                $return_limit_date=$this->__extractBookPartText($contentsBook, "返却期限日"),
                $state=BookState::from($this->__extractBookPartText($contentsBook, "状態")),
                $lending_book_id=$this->__extractBookPartText($contentsBook, "資料ＩＤ"),
            );
            if (empty($book->lendingBookId) || empty($book->title) || empty($book->returnLimitDate)) {
                continue;
            }
            $result[] = $book;
        }

        return $result;
    }

    private function __extractBookPartText(string $part_text, string $field_name): string
    {
        preg_match("@[\S\s]+<strong>$field_name</strong><br/>\n[\s]*&nbsp;&nbsp;[\n]*(.+?)[\n]*<br/>[\S\s]+@", $part_text, $matches);
        if (!$matches || count($matches) < 2) {
            return "";
        }
        return trim(html_entity_decode(strip_tags($matches[1])));
    }

    public function extendLendingBook(string $lendingBookId): LendingBook
    {
        $this->__login();

        $lendingBookId = substr($lendingBookId, 0, -1);   // omlの仕様に合わせて、最後の1文字を削る

        // https://web.oml.city.osaka.lg.jp/webopac/moblendtl.do?lenidlist=102588901&sortkey=lmtdt,id/ASC&startpos=1&hitcnt=10&listpos=
        $result = [];
        $next_url = "/webopac/moblendtl.do?lenidlist={$lendingBookId}&sortkey=lmtdt,id/ASC&startpos=1&hitcnt=10&listpos=";
        // $this->logger->log("next_url: " . $next_url);
        $response = $this->client->request(
            "get", $next_url, [
                "cookies" => $this->cookieJar
            ]
        );
        $this->__checkResponse($response);
        // $this->logger->log((string)$response->getBody());

        // get token from response
        $params = $this->__parseLendingExtendPage((string)$response->getBody());

        $response = $this->client->request(
            "post", "/webopac/moblenupd.do", [
                "cookies" => $this->cookieJar,
                "form_params" => $params,
            ]
        );
        $this->__checkResponse($response);
        return $this->__parseLendingExtendResultPage((string)$response->getBody());
    }

    private function __parseLendingExtendPage(string $contents): array
    {
        $params = [];
        foreach (["org.apache.struts.taglib.html.TOKEN", "sortkey", "startpos", "lenidlist"] as $key) {
            $params[$key] = $this->__extractHiddenValue($contents, $key);
        }

        return $params;
    }

    private function __parseLendingExtendResultPage(string $contents): LendingBook
    {
        $contentsBook = preg_replace('/.+<form name="asklenform"/', "", $contents);

        // $contentsBooks = explode('<hr/>', $contents);
        // foreach ($contentsBooks as $contentsBook) {
        return new LendingBook(
            $owner=$this->userId,
            $title=$this->__extractBookPartText($contentsBook, "資料名"),
            $return_limit_date=$this->__extractBookPartText($contentsBook, "返却期限日"),
            $state=BookState::from($this->__extractBookPartText($contentsBook, "状態")),
            $lending_book_id=$this->__extractBookPartText($contentsBook, "資料ＩＤ"),
        );
    }

    private function __extractHiddenValue(string $contents, string $key): string
    {
        preg_match('@<input type="hidden" name="' . $key . '" value="(.*?)">@', $contents, $matches);

        if (!$matches || count($matches) < 2) {
            return "";
        }

        return $matches[1];
    }

    public function search(string $keyword="", string $title="", string $author="", int $page=1, string $order="desc"): array
    {
        $result = [];

        $next_url = "/webopac/mobctlsrh.do?cmd=search";
        $form_params = [
            "startpos" => 1 + ($page - 1) * 5,
            "srhclm1" => "words",
            "valclm1" => $keyword,
            "mchclm1" => "partial",
            "optclm1" => "and",
            "srhclm2" => "title",
            "valclm2" => $title,
            "mchclm2" => "partial",
            "optclm2" => "and",
            "srhclm3" => "auth",
            "valclm3" => $author,
            "mchclm3" => "partial",
            "gcattp" => "bk",
            "gmdsmd" => "",
            "holph" => "",
            "holar" => "",
            "year" => "",
            "year2" => "",
            "sortkey" => "syear,scntry/" . strtoupper($order),
            "lang_type" => "T",
            "lang" => "JPN",
        ];
        // $this->logger->log($form_params);
        $response = $this->client->request(
            "post", $next_url, [
                "headers" => $this->__getHeaders(),
                "cookies" => $this->cookieJar,
                "form_params" => $form_params,
            ]
        );
        $this->__checkResponse($response);
        $body = (string)$response->getBody();
        // $this->logger->log((string)$response->getBody());

        if (strpos($body, "資料の検索可能件数(10000)を超過しました") !== false) {
            throw new \Exception("検索結果が多すぎます。絞り込みしてください。");
        }

        if (strpos($body, "- 検索結果一覧 -") !== false) {
            return $this->__parseSearchResultPage($body);
        }
        if (strpos($body, "- 書誌詳細 -") !== false) {
            $bookInfo = $this->__parseBookDetailPage($body);
            return [
                new ListedBook($bookInfo["title"] . OmlBook::TITLE_DELIMITER . $bookInfo["author"] . OmlBook::TITLE_DELIMITER . $bookInfo["published_by"] . OmlBook::TITLE_DELIMITER . $bookInfo["published_year"], $bookInfo["book_id"])
            ];
        }
        return [];
    }

    private function __parseSearchResultPage(string $contents): array
    {
        // 不要部分除去
        $contents = preg_replace('/[\S\s]+?- 検索結果一覧 -/m', "", $contents);
        $contents = preg_replace('/<form name="catsrhform"[\S\s]+/m', "", $contents);

        $result = [];
        $contentsBooks = explode('</li>', $contents);
        foreach ($contentsBooks as $contentsBook) {
            // <ui data-role="listview">
            // <li>
            //         <a href="mobcatdbl.do;jsessionid=C951BE80C85B5D36EE7572BA27BE62EE.webopac-02?cmd=search&pkey=0015365135&currentpos=1" accesskey="1">
            //              うえをむいて名探偵([ミルキー杉山のあなたも名探偵シリーズ] [25])∥杉山 亮/作∥偕成社∥2023.5∥Fスキヤ◇Fスキヤ◇913.6</a>
            //         </li>
            preg_match("@[\S\s]+?<li>\n[\s\t]+?<a href=\"mobcatdbl\.do.+?cmd=search&pkey=([0-9]+?)&.+\n[\s\t]*(.+?)</a>@", $contentsBook, $matches);

            if (empty($matches)) {
                continue;
            }

            $book = new ListedBook(
                $title=$matches[2],
                $reservedBookId=$matches[1],
            );
            $result[] = $book;
        }

        return $result;
    }

    public function reserve(string $reservedBookId): ReservedBook
    {
        $this->__login();

        $next_url = "/webopac/mobidf.do?cmd=init&next=mobmopslc";
        $form_params = [
            "reqType" => "_NEW",
            "bibid" => $reservedBookId,
        ];
        // $this->logger->log($form_params);
        $response = $this->client->request(
            "post", $next_url, [
                "headers" => $this->__getHeaders(),
                "cookies" => $this->cookieJar,
                "form_params" => $form_params,
            ]
        );
        $this->__checkResponse($response);
        // file_put_contents("hoge1.html", (string)$response->getBody());

        $next_url = "/webopac/mobrsvslc.do";
        $form_params = [
            "hopar" => "68",
            "renrak" => "1",
        ];
        // $this->logger->log($form_params);
        $response = $this->client->request(
            "post", $next_url, [
                "headers" => $this->__getHeaders(),
                "cookies" => $this->cookieJar,
                "form_params" => $form_params,
            ]
        );
        $this->__checkResponse($response);
        $body = (string)$response->getBody();
        // file_put_contents("hoge2.html", $body);
        $this->__checkReserveConfirmPage($body);
        $token = $this->__extractHiddenValue($body, "org.apache.struts.taglib.html.TOKEN");

        $next_url = "/webopac/mobrsvchk.do";
        $form_params = [
            "org.apache.struts.taglib.html.TOKEN" => $token,
            "reqType" => "_NEW",
        ];
        $response = $this->client->request(
            "post", $next_url, [
                "headers" => $this->__getHeaders(),
                "cookies" => $this->cookieJar,
                "form_params" => $form_params,
            ]
        );
        $this->__checkResponse($response);
        // file_put_contents("hoge3.html", (string)$response->getBody());
        $body = (string)$response->getBody();
        foreach (["- 予約申込完了 -", "下記の内容で予約の申込を行いました"] as $checkStr) {
            if (strpos($body, $checkStr) === false) {
                throw new \Exception("Failed to reserve, checkStr [{$checkStr}] not found.");
            }
        }

        $bookInfo = $this->__parseReserveResultPage($body);
        return new ReservedBook($this->userId, $bookInfo["title"], 999, $reservedBookId, "", BookState::Waiting, "", "");
    }

    private function __parseReserveResultPage(string $content): array
    {
        $result = [];

        // <tr valign="top">
        //     <td colspan="7">
        //         <B>タイトル</B>
        //     </td>
        // </tr>
        // <tr>
        //     <td >
        //         &nbsp;&nbsp;</td>
        //     <td colspan="6">
        //         まちがいなく名探偵([ミルキー杉山のあなたも名探偵シリーズ] [22])∥杉山 亮/作∥偕成社∥20…</td>
        // </tr>
        preg_match('@<td colspan="7">\n\s+?<B>タイトル</B>\n\s+?</td>\n\s+?</tr>\n\s+?<tr>\n[\S\s]+?<td colspan="6">\n\s+?(.+?)</td>@', $content, $matches);
        if (empty($matches)) {
            throw new \Exception("Failed to parse reserve result page: \n" . $content);
        }
        $result["title"] = trim($matches[1]);

        return $result;
    }

    private function __checkReserveConfirmPage(string $contents): void
    {
        // 不要部分除去
        $contents = preg_replace('/[\S\s]+?- 予約確認 -/m', "", $contents);
        $contents = preg_replace('/<div data-role="controlgroup">[\S\s]+/m', "", $contents);

        if (!strpos($contents, "予約可能な資料が存在しません")) {
            return;
        }

        preg_match("@<B>予約不可理由</B>[\S\s]+?<td colspan=\"6\">[\s](.+?)\n</td>@", $contents, $matches);

        throw new \Exception("エラー: " . $matches[1]);
    }

    public function getBookReserveInfo(string $bookId): array
    {
        // 一度だけ検索してからリクエストする（でないと、検索条件ないエラーになる）。早く返すために存在しない書籍名
        $this->search("日本プロ野球の歴史");

        $result = [];
        // https://web.oml.city.osaka.lg.jp/webopac/mobcatdbl.do?cmd=search&pkey=0012200573&currentpos=1558
        $next_url = "/webopac/mobcatdbl.do?cmd=search&pkey=" . $bookId;
        $response = $this->client->request(
            "get", $next_url, [
                "headers" => $this->__getHeaders(),
                "cookies" => $this->cookieJar,
            ]
        );
        $this->__checkResponse($response);
        // file_put_contents("hoge.txt", (string)$response->getBody());

        return $this->__parseBookDetailPage((string)$response->getBody());
    }

    private function __parseBookDetailPage(string $contents): array
    {
        // 不要部分除去
        $contents = preg_replace('/[\S\s]+?- 書誌詳細 -/m', "", $contents);
        $contents = preg_replace('/<form method="post" action="mobidf.do[\S\s]+/m', "", $contents);

        $result = [
            "books" => 0,
            "reserves" => 0,
        ];

        $result["book_id"] = $this->__extractBookDetailText($contents, "書誌ID");
        $result["title"] = $this->__extractBookDetailText($contents, "タイトル");
        $result["author"] = $this->__extractBookDetailText($contents, "著者名");
        $result["published_by"] = $this->__extractBookDetailText($contents, "出版者");
        $result["published_year"] = $this->__extractBookDetailText($contents, "出版年");

        // 所蔵数
        preg_match("@<dd>([0-9]+?)&nbsp;件の所蔵があります</dd>@", $contents, $matches);
        if (!empty($matches)) {
            $result["books"] = (int)$matches[1];
        }
        // 予約数
        preg_match("@<dd>([0-9]+?)&nbsp;件の予約があります</dd>@", $contents, $matches);
        if (!empty($matches)) {
            $result["reserves"] = (int)$matches[1];
        }
        // 予想待ち週
        $result["waitWeeks"] = $result["books"] > 0 ? (int)(ceil($result["reserves"] / $result["books"]) * 2) : 999;

        return $result;
    }

    private function __extractBookDetailText(string $bookDetailText, string $fieldName): string
    {
        preg_match("@<dt><strong>{$fieldName}</strong></dt>\n\s+<dd>(.+?)</dd>@", $bookDetailText, $matches);
        if (!empty($matches)) {
            return $matches[1];
        }
        return "";
    }

    // public function getBookContent(string $bookId): array
    // {
    //     // 一度だけ検索してからリクエストする（でないと、検索条件ないエラーになる）。早く返すために存在しない書籍名
    //     $this->search("日本プロ野球の歴史");

    //     // https://web.oml.city.osaka.lg.jp/webopac/mobcatdbl.do?cmd=bibcte&bibid=0015485620&currentpos=5
    //     $next_url = "/webopac/mobcatdbl.do?cmd=bibcte&bibid=" . $bookId;
    //     $response = $this->client->request(
    //         "get", $next_url, [
    //             "headers" => $this->__getHeaders(),
    //             "cookies" => $this->cookieJar,
    //         ]
    //     );
    //     $this->__checkResponse($response);

    //     $contents = (string)$response->getBody();
    //     // file_put_contents("hoge.txt", (string)$response->getBody());

    //     $result = [];
    //     preg_match("@<dt><strong>内容紹介</strong></dt>[\S\s]+?<dd>(.+?)</dd>@", $contents, $matches);
    //     if (!empty($matches)) {
    //         $result["content"] = $matches[1];
    //     }

    //     return $result;
    // }

    public function cancelReservation(string $bookId, string $changingId, int $reserveCount): void
    {
        $this->__login();

        $next_url = "/webopac/mobrsvlst.do?cmddel=cmddel&svcidlist={$changingId}&startpos=1&hitcnt={$reserveCount}&listcnt=3&sortkey=rsvst,canrs,reqdt/DESC";
        // $this->logger->log($form_params);
        $response = $this->client->request(
            "post", $next_url, [
                "headers" => $this->__getHeaders(),
                "cookies" => $this->cookieJar,
            ]
        );
        $body = (string)$response->getBody();
        // file_put_contents("hoge2.html", $body);
        $form_params = $this->__parseCancelReservationInputPage($body);

        $next_url = "/webopac/mobrsvdel.do";
        $response = $this->client->request(
            "post", $next_url, [
                "headers" => $this->__getHeaders(),
                "cookies" => $this->cookieJar,
                "form_params" => $form_params,
            ]
        );
        $this->__checkResponse($response);
        // file_put_contents("hoge3.html", (string)$response->getBody());
        $body = (string)$response->getBody();
        foreach (["-予約の削除完了-", "以下の予約の取消を行いました。"] as $checkStr) {
            if (strpos($body, $checkStr) === false) {
                throw new \Exception("Failed to cancel, checkStr [{$checkStr}] not found.");
            }
        }
    }

    private function __parseCancelReservationInputPage(string $contents): array
    {
        $result = [];

        foreach (["org.apache.struts.taglib.html.TOKEN", "startpos", "listpos", "hitcnt", "listcnt", "sortkey", "svcidlist"] as $key) {
            $result[$key] = $this->__extractHiddenValue($contents, $key);
        }

        return $result;
    }
}
