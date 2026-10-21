<?php

namespace App\Modules\Todo\Enums;

enum TodoPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
}
