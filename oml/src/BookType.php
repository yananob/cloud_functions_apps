<?php declare(strict_types=1);

namespace MyApp;

enum BookType: string
{
    case Reserved = "reserved";
    case Lending = "lending";
}
