<?php

namespace App\Modules\Infrastructure\Enums;

enum ProviderType: string
{
    case DigitalOcean = 'digitalocean';
    case Hetzner = 'hetzner';
    case Vultr = 'vultr';
    case Aws = 'aws';
    case Manual = 'manual';
}
