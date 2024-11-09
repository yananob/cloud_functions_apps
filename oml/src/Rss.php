<?php

declare(strict_types=1);

namespace MyApp;

use GuzzleHttp\Client;

class Rss
{
    public function __construct(private ?RssType $rssType = null, private ?string $filePath = null)
    {
    }

    private function __getUrl(string $category): string
    {
        switch ($this->rssType) {
            case RssType::UpcomingAdult:
                return "https://web.oml.city.osaka.lg.jp/webopac_i_ja/newexe.do?REQTP=RSS&locale=ja&newlv1=1&newlv2={$category}";
            case RssType::UpcomingChild:
                return "https://web.oml.city.osaka.lg.jp/webopac_i_ja/newexe.do?REQTP=RSS&locale=ja&newlv1=2&newlv2={$category}";
            case RssType::LendingBest:
                return "https://web.oml.city.osaka.lg.jp/webopac_i_ja/besexe.do?REQTP=RSS&target=b{$category}&locale=ja&beslv1=1";
            case RssType::ReserveBest:
                return "https://web.oml.city.osaka.lg.jp/webopac_i_ja/brqexe.do?REQTP=RSS&target=q{$category}&locale=ja&beslv1=1";
            default:
                throw new \Exception("Unknown RssType: " . $this->rssType->value);
        }
    }

    private function __fetch(string $url): string
    {
        $client = new Client([
            'timeout'  => 10.0,
        ]);
        $response = $client->request(
            "get",
            $url,
        );
        if (!in_array($response->getStatusCode(), [200])) {
            throw new \Exception("Request error. [" . $response->getStatusCode() . "] " . $response->getReasonPhrase());
        }
        return (string)$response->getBody();
    }

    public function listBooks(string $category): array
    {
        if (!empty($this->rssType)) {
            $feed = $this->__fetch($this->__getUrl($category));
        } else {
            $feed = file_get_contents($this->filePath);
        }
        $rss = simplexml_load_string($feed, "SimpleXMLElement", LIBXML_NOCDATA);

        $result = [];
        foreach ($rss->item as $item) {
            // https://web.oml.city.osaka.lg.jp/webopac_i_ja/ufirdi.do?ufi_target=catdbl&ufi_locale=ja&pkey=0015547923
            parse_str(parse_url($item->link->__toString(), PHP_URL_QUERY), $query);
            $result[] = new ListedBook(
                trim($item->title->__toString()) . OmlBook::TITLE_DELIMITER . trim($item->description->__toString()),
                $query["pkey"]
            );
        }
        return $result;
    }
}
