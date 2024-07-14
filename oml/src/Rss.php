<?php

declare(strict_types=1);

namespace MyApp;

use GuzzleHttp\Client;

class Rss
{
    public function __construct(private ?RssType $rssType = null, private ?string $filePath = null)
    {
    }

    private function __getUrl(int $lv2): string
    {
        switch ($this->rssType) {
            case RssType::Upcoming:
                return "https://web.oml.city.osaka.lg.jp/webopac_i_ja/newexe.do?REQTP=RSS&locale=ja&newlv1=1&newlv2={$lv2}";
            case RssType::LendingBest:
                return "https://web.oml.city.osaka.lg.jp/webopac_i_ja/besexe.do?REQTP=RSS&target=b202405&locale=ja&beslv1=1";
            case RssType::ReserveBest:
                return "https://web.oml.city.osaka.lg.jp/webopac_i_ja/brqexe.do?REQTP=RSS&target=q202405&locale=ja&beslv1=1";
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

    public function listBooks(int $lv2): array
    {
        if (!empty($this->rssType)) {
            $feed = $this->__fetch($this->__getUrl($lv2));
        } else {
            $feed = file_get_contents($this->filePath);
        }
        $rss = simplexml_load_string($feed);
        
        $result = [];
        foreach ($rss->item as $item) {
            // https://web.oml.city.osaka.lg.jp/webopac_i_ja/ufirdi.do?ufi_target=catdbl&ufi_locale=ja&pkey=0015547923
            parse_str(parse_url($item->link->__toString(), PHP_URL_QUERY), $query);
            $result[] = new ListedBook(trim($item->title->__toString()), $query["pkey"]);
        }
        return $result;
    }
}
