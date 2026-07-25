<?php

use App\Models\Business;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Business::query()
            ->where('status', Business::STATUS_ACTIVE)
            ->with('owner')
            ->each(function (Business $business): void {
                if ($business->owner instanceof User) {
                    Setting::ensureDefaultFor($business->owner);
                }
            });
    }

    public function down(): void
    {
        // Intentionally preserve settings because a client may have customized
        // the automatically created print profile after this migration ran.
    }
};
