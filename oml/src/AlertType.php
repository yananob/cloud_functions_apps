<?php declare(strict_types=1);

namespace MyApp;

enum AlertType
{
    case DuplicatedReserved;
    case DuplicatedReservedAndLending;
    case ReturnLimit;
    case KeepLimit;
    case AutoExtended;
}
