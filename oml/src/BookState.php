<?php declare(strict_types=1);

namespace MyApp;

enum BookState: string
{
    // for reserved
    case Waiting = "待ち";
    case Keeping = "取置中";
    case Expired = "期限切れ";
    // for lending
    case None = "";
    case Extended = "延長済";
    case Overdue = "延滞";
    case Reserved = "次予約有";
}
