<?php

declare(strict_types=1);

namespace MyApp;

enum RssType: string
{
    case UpcomingAdult = "upcoming_adult";
    case UpcomingChild = "upcoming_child";
    case LendingBest = "lending_best";
    case ReserveBest = "reserve_best";
}
