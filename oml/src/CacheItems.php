<?php declare(strict_types=1);

namespace MyApp;

enum CacheItems: string
{
    case Accounts = "oml_accounts";
    case ReservedBooks = "oml_reservedbooks";
    case LendingBooks = "oml_lendingbooks";
    case UpdatedTimestamps = "oml_updatedtimestamps";
}
