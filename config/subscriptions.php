<?php

return [
    /*
    | Shops that are still not on any subscription plan on this date are suspended
    | (data is kept; the super admin can reactivate them). Until then they see a
    | warning with the date. Leave empty to turn the deadline off.
    */
    'no_plan_deadline' => env('SUBSCRIPTION_NO_PLAN_DEADLINE', '2026-12-01'),
];
