<?php

namespace App\Modules\Infrastructure\Enums;

enum AgentCommandType: string
{
    case Bootstrap = 'bootstrap';
    case Deploy = 'deploy';
    case UpdateProxyConfig = 'update_proxy_config';
    case PushSshKeys = 'push_ssh_keys';
    case RestartProcess = 'restart_process';
    case Custom = 'custom';
}
