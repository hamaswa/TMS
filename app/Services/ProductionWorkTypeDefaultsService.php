<?php

namespace App\Services;

use App\Models\WorkType;
use Illuminate\Support\Collection;

class ProductionWorkTypeDefaultsService
{
    private const DEFAULTS = [
        'stitching' => 'سلائی',
        'cutting' => 'کٹائی',
        'embroidery' => 'کڑھائی',
        'finishing' => 'فنشنگ اور بٹن',
        'ironing' => 'استری',
        'quality_check' => 'معیار کی جانچ',
    ];

    public function forOwner(int $ownerId): Collection
    {
        foreach (self::DEFAULTS as $code => $name) {
            WorkType::firstOrCreate(
                ['user_id' => $ownerId, 'code' => $code],
                ['name' => $name, 'category' => 'production', 'is_system' => true, 'active' => true],
            );
        }

        return WorkType::where('user_id', $ownerId)
            ->where('active', true)
            ->orderBy('name')
            ->get();
    }
}
