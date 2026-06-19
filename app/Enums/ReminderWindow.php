<?php

declare(strict_types=1);

namespace App\Enums;

enum ReminderWindow: string
{
    case ThreeDays = 'in 3 days';
    case TwentyFourHours = 'tomorrow';
}
