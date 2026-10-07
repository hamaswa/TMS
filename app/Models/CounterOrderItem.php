<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CounterOrderItem extends Model
{
    public const TYPE_CLOTH = 'cloth';
    public const TYPE_TAILORING = 'tailoring';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'counter_order_id', 'linked_item_id', 'type', 'status', 'cloth_id',
        'measurement_profile_id', 'measurement_template_id', 'tailor_id', 'rate_id',
        'quantity', 'length', 'unit_price', 'line_total', 'due_date', 'details', 'note',
        'source_record_type', 'source_record_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2', 'length' => 'decimal:2', 'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2', 'due_date' => 'date', 'details' => 'array',
        ];
    }

    public function counterOrder() { return $this->belongsTo(CounterOrder::class); }
    public function linkedItem() { return $this->belongsTo(self::class, 'linked_item_id'); }
    public function cloth() { return $this->belongsTo(Cloth::class); }
    public function measurementProfile() { return $this->belongsTo(Customers::class, 'measurement_profile_id'); }
    public function measurementTemplate() { return $this->belongsTo(MeasurementTemplate::class); }
    public function tailor() { return $this->belongsTo(Tailor::class); }
}
