<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\BusinessStatusHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SuspendShopsWithoutPlan extends Command
{
    protected $signature = 'subscriptions:suspend-shops-without-plan
        {--dry-run : List the shops that would be suspended without changing anything}';

    protected $description = 'Suspend active shops that are still not on any subscription plan once the no-plan deadline has passed';

    public function handle(): int
    {
        $deadline = Business::noPlanDeadline();
        if (! $deadline) {
            $this->info('No deadline is set (SUBSCRIPTION_NO_PLAN_DEADLINE); nothing to do.');

            return self::SUCCESS;
        }
        if (now()->lt($deadline)) {
            $this->info("Deadline is {$deadline->toDateString()}; shops are only warned until then.");

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $suspended = 0;

        Business::query()->withoutPlan()->orderBy('id')->chunkById(100, function ($businesses) use ($dryRun, &$suspended) {
            foreach ($businesses as $business) {
                $this->line(($dryRun ? 'Would suspend' : 'Suspending')." #{$business->id} {$business->name}");
                if ($dryRun) {
                    $suspended++;

                    continue;
                }

                DB::transaction(function () use ($business, &$suspended) {
                    $locked = Business::query()->withoutPlan()->lockForUpdate()->find($business->id);
                    if (! $locked) {
                        return; // Got a plan or changed status since the chunk was read.
                    }

                    $locked->forceFill([
                        'status' => Business::STATUS_SUSPENDED,
                        'status_changed_at' => now(),
                        'status_changed_by_user_id' => null,
                        'status_reason' => Business::NO_PLAN_SUSPENSION_REASON,
                    ])->save();
                    BusinessStatusHistory::create([
                        'business_id' => $locked->id,
                        'from_status' => Business::STATUS_ACTIVE,
                        'to_status' => Business::STATUS_SUSPENDED,
                        'changed_by_user_id' => null,
                        'reason' => Business::NO_PLAN_SUSPENSION_REASON,
                        'created_at' => now(),
                    ]);
                    $suspended++;
                });
            }
        });

        $this->info(($dryRun ? 'Shops that would be suspended: ' : 'Shops suspended: ').$suspended);

        return self::SUCCESS;
    }
}
