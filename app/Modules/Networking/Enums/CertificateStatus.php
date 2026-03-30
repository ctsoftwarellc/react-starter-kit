<?php

namespace App\Modules\Networking\Enums;

enum CertificateStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Expired = 'expired';
    case Failed = 'failed';
}
