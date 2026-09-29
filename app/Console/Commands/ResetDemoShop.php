<?php

namespace App\Console\Commands;

use App\Services\DemoShopResetter;
use Illuminate\Console\Command;

class ResetDemoShop extends Command
{
    protected $signature = 'demo:reset {--force : Reset even when the public demo button is disabled}';

    protected $description = 'Restore the isolated public demo shop to its original sample data';

    public function handle(DemoShopResetter $resetter): int
    {
        if (! config('demo.enabled') && ! $this->option('force')) {
            $this->components->info('Public demo is disabled; nothing was reset.');

            return self::SUCCESS;
        }

        $resetter->reset();
        $this->components->info('The public demo shop has been restored.');

        return self::SUCCESS;
    }
}
