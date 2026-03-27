<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | The filesystem disk used for artifacts, pipeline logs, and backups.
    | Configure an S3-compatible disk for production. Falls back to local
    | storage for development.
    |
    */

    'storage_disk' => env('HELM_STORAGE_DISK', 'artifacts'),

    /*
    |--------------------------------------------------------------------------
    | Agent
    |--------------------------------------------------------------------------
    */

    'agent' => [
        'heartbeat_interval' => 30,        // seconds between agent heartbeats
        'offline_threshold' => 90,          // seconds before agent is considered offline
        'command_ttl' => 300,               // seconds before unprocessed commands expire
        'command_poll_interval' => 10,      // seconds between agent command polls
    ],

    /*
    |--------------------------------------------------------------------------
    | Runner
    |--------------------------------------------------------------------------
    */

    'runner' => [
        'heartbeat_interval' => 15,
        'offline_threshold' => 60,
        'poll_interval' => 5,              // seconds between job polls
        'default_job_timeout' => 900,       // 15 minutes
        'max_job_timeout' => 3600,          // 1 hour
        'log_chunk_size' => 65536,          // 64KB log buffer
        'log_flush_interval' => 5,          // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Pipeline
    |--------------------------------------------------------------------------
    */

    'pipeline' => [
        'max_run_duration' => 3600,         // 1 hour max for entire run
        'default_job_timeout' => 900,       // 15 minutes per job
        'max_retries' => 2,
        'concurrency' => 1,                 // runs per pipeline
    ],

    /*
    |--------------------------------------------------------------------------
    | Deployment
    |--------------------------------------------------------------------------
    */

    'deployment' => [
        'health_check_delay' => 5,          // seconds after deploy before health check
        'health_check_retries' => 3,
        'health_check_interval' => 5,       // seconds between retries
        'health_check_timeout' => 10,       // seconds per check
        'release_retention' => 5,           // releases kept on disk per environment
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    */

    'retention' => [
        'artifacts_days' => 30,             // days to keep superseded artifacts
        'pipeline_logs_days' => 30,
        'audit_logs_days' => 365,
        'metrics_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Server Bootstrap
    |--------------------------------------------------------------------------
    */

    'bootstrap' => [
        'max_retries' => 3,
        'supported_os' => ['ubuntu-22.04', 'ubuntu-24.04'],
        'default_ssh_user' => 'root',
        'default_ssh_port' => 22,
    ],

];
