<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Customers;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Fills a shop with test customers up to one below its plan's customer limit,
 * so the limit can be checked by hand: the next customer saves, the one after is refused.
 *
 * Pick the shop with SEED_SHOP_CODE; without it the only shop on a customer-limited plan is used.
 */
class CustomerLimitQaSeeder extends Seeder
{
    public function run(): void
    {
        $business = $this->business();
        $limit = $business->subscriptionLimit('max_customers');
        if ($limit === null) {
            throw new RuntimeException("Shop {$business->shop_code} has no customer limit on its current plan.");
        }

        $ownerId = (int) $business->owner_user_id;
        $used = Customers::where('user_id', $ownerId)
            ->whereNull('parent_id')
            ->where(fn ($query) => $query->where('is_walk_in', false)->orWhereNull('is_walk_in'))
            ->count();
        $target = $limit - 1;
        $toAdd = max(0, $target - $used);

        for ($i = 1; $i <= $toAdd; $i++) {
            $number = $used + $i;
            Customers::create([
                'name' => 'QA Customer '.$number,
                // 0399 + 7 digits keeps every test number unique and easy to spot.
                'phone_number1' => '0399'.str_pad((string) (($ownerId * 10000 + $number) % 10000000), 7, '0', STR_PAD_LEFT),
                'user_id' => $ownerId,
                'acquisition_source' => 'qa_seed',
            ]);
        }

        $this->command?->info("Shop {$business->shop_code}: limit {$limit}, had {$used}, added {$toAdd}, now ".($used + $toAdd).'.');
    }

    private function business(): Business
    {
        if ($code = env('SEED_SHOP_CODE')) {
            return Business::where('shop_code', $code)->firstOrFail();
        }

        $shops = Business::whereHas('activeSubscription', fn ($query) => $query->whereNotNull('max_customers'))->get();
        if ($shops->count() !== 1) {
            throw new RuntimeException('Set SEED_SHOP_CODE to one of: '.($shops->pluck('shop_code')->implode(', ') ?: 'no shop has a customer-limited plan'));
        }

        return $shops->first();
    }
}
