<?php declare(strict_types=1);

namespace MyApp;

enum Command: string
{
    case Main = "main";

    case ListReserved = "list_reserved";
    case ListLending = "list_lending";
    case UpdateAllReserved = "update_all_reserved";
    case UpdateAllLending = "update_all_lending";
    case UpdateAccountReserved = "update_account_reserved";
    case UpdateAccountLending = "update_account_lending";

    case Reserve = "reserve";
    case JsonSearch = "json-search";
    case JsonShowList = "json-showlist";
    case JsonReserve = "json-reserve";
    case JsonBookReserveInfo = "json-bookreserveinfo";
    case JsonReserveAgain = "json-reserveagain";
    case JsonCancelReservation = "json-cancelreservation";
    case JsonExtend = "json-extend";
    // case JsonBookContent = "json-bookcontent";
}
