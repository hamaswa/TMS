<?php

return [
    /*
    | A hub is a Laravel instance running inside a shop. All shop browsers and
    | sales-agent phones use it as their API while the cloud is unavailable.
    */
    'mode' => env('SHOP_HUB_MODE', 'cloud'),
    'id' => env('SHOP_HUB_ID'),
    'name' => env('SHOP_HUB_NAME', 'BuyNStitch Shop Hub'),
    'cloud_url' => env('SHOP_HUB_CLOUD_URL'),
    'sync_key' => env('SHOP_HUB_SYNC_KEY'),
    'batch_size' => (int) env('SHOP_HUB_BATCH_SIZE', 50),
];
