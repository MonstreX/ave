<?php

namespace Monstrex\Ave\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CacheClearCommand extends Command
{
    protected $signature = 'ave:cache-clear';

    protected $description = 'Clear Ave discovery cache';

    public function handle(): int
    {
        Cache::forget('ave.discovery');

        $this->info('Ave discovery cache cleared successfully.');

        return self::SUCCESS;
    }
}
