<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class OptionTypesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('option_types')->upsert([
            [
                'id' => 1,
                'slug' => 'add_sewing_type',
                'Name' => 'سیلائی',
                'type' => 'swingtype',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'user_id'    => 1,
            ],
            [
                'id' => 2,
                'slug' => 'add_shirt_button_type',
                'Name' => 'شرٹ بٹن',
                'type' => 'shirtbutton',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'user_id'    => 1,
            ],
            [
                'id' => 3,
                'slug' => 'add_neck_type',
                'Name' => 'گلہ',
                'type' => 'necktype',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'user_id'    => 1,
            ],
            [
                'id' => 4,
                'slug' => 'add_sleeve_opening_type',
                'Name' => 'کف',
                'type' => 'sleeve',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'user_id'    => 1,
            ],
            [
                'id' => 5,
                'slug' => 'add_pocket_type',
                'Name' => 'جیب',
                'type' => 'jeab',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'user_id'    => 1,
            ],
            [
                'id' => 6,
                'slug' => 'add_button_type',
                'Name' => 'بٹن',
                'type' => 'button',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'user_id'    => 1,
            ],
            [
                'id' => 7,
                'slug' => 'plate_type',
                'Name' => 'پلیٹ',
                'type' => 'plate_type',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'user_id'    => 1,
            ],
            [
                'id' => 8,
                'slug' => 'add_daaman_type',
                'Name' => 'دامن',
                'type' => 'daaman',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'user_id'    => 1,
            ],
        ], ['id'], ['slug', 'Name', 'type', 'updated_at', 'user_id']);
    }
}
