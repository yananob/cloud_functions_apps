<?php declare(strict_types=1);

namespace MyApp;

final class Query
{
    public function __construct() {
    }

    public function build(array $target): string
    {
        $q = "";
        if (array_key_exists("keyword", $target)) {
            $q .= $target["keyword"];
        };
    
        if (array_key_exists("from", $target)) {
            $q .= " from:" . $target["from"];
        };
    
        if (array_key_exists("to", $target)) {
            $q .= " to:" . $target["to"];
        };
    
        if (array_key_exists("subject", $target)) {
            $q .= " subject:" . $target["subject"];
        };
    
        if (array_key_exists("label", $target)) {
            $q .= " label:" . $target["label"];
        };
    
        if (array_key_exists("date_before", $target)) {
            $targetDate = (new \DateTime())->sub(new \DateInterval($target["date_before"]))->format('Y/m/d');
            $q .= " before:${targetDate}";
        };
    
        return $q;
    }
}
