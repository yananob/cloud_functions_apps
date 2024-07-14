<?php

declare(strict_types=1);

namespace MyApp;

class Rss
{
    public function __construct(private ?RssType $rssType = null, private ?string $filePath = null)
    {
    }

    public function listBooks(int $lv2): array
    {
        if ($this->rssType) {
            echo "DUMMY";
        }

        $feed = file_get_contents($this->filePath);
        $rss = simplexml_load_string($feed);
        // var_dump($rss);
        $result = [];
        foreach ($rss->item as $item) {
            // https://web.oml.city.osaka.lg.jp/webopac_i_ja/ufirdi.do?ufi_target=catdbl&ufi_locale=ja&pkey=0015547923
            parse_str(parse_url($item->link->__toString(), PHP_URL_QUERY), $query);
            $result[] = new ListedBook(trim($item->title->__toString()), $query["pkey"]);
        }
        return $result;
    }
}
