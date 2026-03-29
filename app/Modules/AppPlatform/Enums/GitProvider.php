<?php

namespace App\Modules\AppPlatform\Enums;

enum GitProvider: string
{
    case Github = 'github';
    case Gitlab = 'gitlab';
    case Bitbucket = 'bitbucket';
}
