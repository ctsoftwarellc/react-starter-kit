<?php

namespace App\Support\Enums;

enum QueueName: string
{
    case Default = 'default';
    case Infrastructure = 'infrastructure';
    case Pipeline = 'pipeline';
    case Deployment = 'deployment';
    case Notifications = 'notifications';
    case Maintenance = 'maintenance';
}
