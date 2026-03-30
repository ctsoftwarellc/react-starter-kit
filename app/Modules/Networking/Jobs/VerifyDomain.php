<?php

namespace App\Modules\Networking\Jobs;

use App\Modules\Networking\Actions\VerifyDomain as VerifyDomainAction;
use App\Modules\Networking\Models\Domain;
use App\Support\Enums\QueueName;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyDomain implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public Domain $domain) {}

    public function handle(): void
    {
        (new VerifyDomainAction)->execute($this->domain);
    }

    public function queue(): string
    {
        return QueueName::Default->value;
    }
}
