<?php

namespace App\Modules\AppPlatform\Enums;

enum Runtime: string
{
    case Php = 'php';
    case Node = 'node';
    case Python = 'python';
    case Go = 'go';
}
