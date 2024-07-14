<?php

declare(strict_types=1);

namespace MyApp;

enum RssType: string
{
    case Upcoming = "upcoming";
    case LendingBest = "lending_best";
    case ReserveBest = "reserve_best";
}
