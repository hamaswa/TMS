<?php

namespace App\Models;

use App\Models\Relations\HasManyByText;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public const STATUSES = ['unassigned', 'assigned', 'cutting', 'stitching', 'trial', 'ready', 'delivered', 'cancelled'];

    public const STATUS_LABELS = [
        'unassigned' => 'درزی مقرر ہونا باقی',
        'assigned' => 'درزی مقرر',
        'cutting' => 'کٹائی',
        'stitching' => 'سلائی',
        'trial' => 'ٹرائل',
        'ready' => 'تیار',
        'delivered' => 'حوالہ شدہ',
        'cancelled' => 'منسوخ',
    ];

    /** Simple mode shows every workshop step as one "in the workshop" status. */
    public const SIMPLE_STATUS_LABELS = [
        'unassigned' => 'درزی مقرر ہونا باقی',
        'assigned' => 'کارخانے میں ہے',
        'cutting' => 'کارخانے میں ہے',
        'stitching' => 'کارخانے میں ہے',
        'trial' => 'کارخانے میں ہے',
        'ready' => 'تیار ہے',
        'delivered' => 'حوالہ شدہ',
        'cancelled' => 'منسوخ',
    ];

    /** Not yet stitched, so measurement changes still reach the tailor. */
    public const OPEN_STATUSES = ['unassigned', 'assigned', 'cutting', 'stitching', 'trial'];

    /** Statuses grouped under one simple-mode filter value. */
    public const SIMPLE_WORKSHOP_STATUSES = ['assigned', 'cutting', 'stitching', 'trial'];

    public static function statusLabelsFor(bool $detailed): array
    {
        return $detailed ? self::STATUS_LABELS : self::SIMPLE_STATUS_LABELS;
    }

    protected $fillable = ['name', 'sub_customer', 'measurement_template_id', 'suitNum', 'designPrice', 'design','customerId', 'suitQuantity', 'totalPayment', 'userId', 'returnDate', 'tailorId', 'rateId', 'remarks', 'tailor_price', 'rack_no', 'status', 'cancellation_reason', 'status_changed_at', 'started_at', 'ready_at', 'delivered_at', 'cancelled_at', 'cancelled_by_user_id', 'tailor_paid_amount', 'tailor_payment_status'];

    protected $casts = [
        'status_changed_at' => 'datetime',
        'started_at' => 'datetime',
        'ready_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'tailor_paid_amount' => 'decimal:2',
        'tailor_price' => 'decimal:2',
    ];

    public function transactions()
    {
        return HasManyByText::of($this, Transaction::class, 'orderId');
    }

    /**
     * Set outstanding_amount (the sum of each order's transactions) on a page of orders in one query.
     * transactions.orderId and userId are text columns, so ids are compared as text to keep it indexed;
     * a per-order withSum() made MySQL convert and rescan the shop's transactions for every order.
     */
    public static function loadOutstanding(\Illuminate\Support\Collection $orders, int $ownerId): void
    {
        if ($orders->isEmpty()) {
            return;
        }
        $totals = Transaction::where('userId', (string) $ownerId)
            ->whereIn('orderId', $orders->map(fn (self $order) => (string) $order->id)->all())
            ->groupBy('orderId')
            ->selectRaw('orderId, SUM(remainingBalance) AS total')
            ->pluck('total', 'orderId');
        $orders->each(fn (self $order) => $order->outstanding_amount = $totals[(string) $order->id] ?? null);
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

    public function workAssignments()
    {
        return $this->hasMany(OrderWorkAssignment::class);
    }

    public function measurementTemplate()
    {
        return $this->belongsTo(MeasurementTemplate::class);
    }

    public function nextStatuses(): array
    {
        return array_column(self::nextStatusOptionsFor((string) $this->status), 'value');
    }

    public function nextStatusOptions(): array
    {
        return self::nextStatusOptionsFor((string) $this->status);
    }

    public static function nextStatusOptionsFor(string $status): array
    {
        $nextStatuses = match ($status) {
            'unassigned' => [],
            'assigned' => ['cutting'],
            'cutting' => ['stitching'],
            'stitching' => ['trial'],
            'trial' => ['stitching', 'ready'],
            'ready' => ['stitching', 'delivered'],
            default => [],
        };

        return collect($nextStatuses)
            ->map(fn (string $status) => [
                'value' => $status,
                'label' => $status === 'stitching' && (in_array('ready', $nextStatuses, true) || in_array('delivered', $nextStatuses, true))
                    ? 'سلائی پر واپس (ترمیم / دوبارہ کام)'
                    : (self::STATUS_LABELS[$status] ?? ucfirst($status)),
            ])
            ->values()
            ->all();
    }

    public function tailorAmountDue(): float
    {
        return round((float) $this->tailor_price * max(1, (int) $this->suitQuantity), 2);
    }
}
