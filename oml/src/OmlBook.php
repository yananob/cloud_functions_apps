<?php declare(strict_types=1);

namespace MyApp;

class OmlBook
{
    public string $title;
    public string $author;
    public string $publishedYear;

    const TITLE_DELIMITER = "∥";

    public function __construct(public string $fullTitle)
    {
        $this->fullTitle = $fullTitle;

        $this->title = $this->__getTitlePart(0);
        $this->author = $this->__getTitlePart(1);
        $this->publishedYear = $this->__getTitlePart(3);
    }

    private function __getTitlePart(int $index): string
    {
        $titles = explode(self::TITLE_DELIMITER, $this->fullTitle);
        if (count($titles) > $index) {
            return $titles[$index];
        }
        else {
            return "";
        }
    }
}
