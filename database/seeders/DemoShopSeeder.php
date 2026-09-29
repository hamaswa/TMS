<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DemoShopSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('demo.email');
        $existing = User::where('email', $email)->first();

        if ($existing) {
            if ($existing->business?->isDemo()) {
                $this->command?->info('The public demo shop already exists.');

                return;
            }

            throw new RuntimeException("The demo email {$email} is already used by a non-demo account.");
        }

        DB::transaction(function () use ($email) {
            $qa = app(QaDemoSeeder::class);
            [$owner, $business] = $qa->owner(
                'Demo Shop Owner',
                $email,
                'BuyNStitch Demo Tailors & Fabrics',
                true,
                true,
                '03000000000',
                'Demo Market, Pakistan',
                true,
            );

            $owner->forceFill([
                'email_verified_at' => now(),
                'password' => Hash::make((string) config('demo.password')),
            ])->save();
            $qa->employees($owner, $business, 'public-demo');
            $qa->tailoringData($owner, true);
            $qa->clothingData($owner, true);
        });

        $this->command?->info("Public demo shop created for {$email}.");
    }
}
