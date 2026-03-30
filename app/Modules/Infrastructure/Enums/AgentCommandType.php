<?php

namespace App\Modules\Infrastructure\Enums;

enum AgentCommandType: string
{
    case Bootstrap = 'bootstrap';
    case Deploy = 'deploy';
    case Rollback = 'rollback';
    case ConfigureRuntime = 'configure_runtime';
    case ConfigureService = 'configure_service';
    case RemoteCommand = 'remote_command';
    case UpdateProxyConfig = 'update_proxy_config';
    case PushSshKeys = 'push_ssh_keys';
    case RestartProcess = 'restart_process';
    case Custom = 'custom';
}
