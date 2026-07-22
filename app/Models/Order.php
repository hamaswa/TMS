<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public const STATUSES = ['assigned', 'cutting', 'stitching', 'trial', 'ready', 'delivered'];

    protected $fillable = ['name', 'sub_customer', 'suitNum', 'designPrice', 'design','customerId', 'suitQuantity', 'totalPayment', 'userId', 'returnDate', 'tailorId', 'rateId', 'remarks', 'tailor_price', 'rack_no', 'status', 'status_changed_at', 'started_at', 'ready_at', 'delivered_at', 'tailor_paid_amount', 'tailor_payment_status'];

    protected $casts = [
        'status_changed_at' => 'datetime',
        'started_at' => 'datetime',
        'ready_at' => 'datetime',
        'delivered_at' => 'datetime',
        'tailor_paid_amount' => 'decimal:2',
        'tailor_price' => 'decimal:2',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'orderId', 'id');
    }

    public function tailor()
    {
        return $this->belongsTo(Tailor::class, 'tailorId', 'id');
    }

    public function customers()
    {
        return $this->belongsTo(Customers::class, 'sub_customer', 'id');
    }

    public function rate()
    {
        return $this->belongsTo('App\Models\Tailorsalary','rateId','id');
    }
    public function tailorRecords()
    {
        return $this->hasMany(TailorRecord::class, 'order_id', 'id');
    }

    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }

    public function notificationDeliveries()
    {
        return $this->hasMany(OrderNotificationDelivery::class);
    }

    public function measurementValues()
    {
        return $this->hasMany(OrderMeasurementValue::class)->orderBy('sort_order');
    }

    public function nextStatuses(): array
    {
        return match ($this->status) {
            'assigned' => ['cutting'],
            'cutting' => ['stitching'],
            'stitching' => ['trial'],
            'trial' => ['stitching', 'ready'],
            'ready' => ['delivered'],
            default => [],
        };
    }

    public function tailorAmountDue(): float
    {
        return round((float) $this->tailor_price * max(1, (int) $this->suitQuantity), 2);
    }
}
