<?php

namespace App\Modules\Todo\Enums;

enum TodoStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
}
