<?php

namespace App\Services;

use App\Models\Customers;
use App\Models\Order;
use App\Models\OrderNotificationDelivery;
use App\Models\Setting;
use App\Notifications\TailorJobStatusNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class OrderLifecycleNotificationService
{
    public const NOTIFIABLE_STAGES = ['cutting', 'trial', 'ready', 'delivered'];

    public function send(Order $order, string $stage, ?string $note = null): ?OrderNotificationDelivery
    {
        if (! in_array($stage, self::NOTIFIABLE_STAGES, true)) {
            return null;
        }

        $existing = OrderNotificationDelivery::where('order_id', $order->id)
            ->where('stage', $stage)
            ->where('channel', 'database')
            ->first();
        if ($existing?->status === 'sent') {
            return $existing;
        }

        $customer = Customers::where('user_id', $order->userId)->find($order->sub_customer);

        if (! $customer) {
            return OrderNotificationDelivery::updateOrCreate(
                ['order_id' => $order->id, 'stage' => $stage, 'channel' => 'database'],
                [
                    'user_id' => $order->userId,
                    'customer_id' => null,
                    'status' => 'skipped',
                    'last_error' => 'The order does not have an available customer account.',
                    'last_attempted_at' => now(),
                ],
            );
        }

        try {
            return DB::transaction(function () use ($order, $stage, $note, $customer) {
                $delivery = OrderNotificationDelivery::firstOrCreate(
                    ['order_id' => $order->id, 'stage' => $stage, 'channel' => 'database'],
                    ['user_id' => $order->userId, 'customer_id' => $customer->id],
                );
                $delivery = OrderNotificationDelivery::lockForUpdate()->findOrFail($delivery->id);

                if ($delivery->status === 'sent') {
                    return $delivery;
                }

                $notification = new TailorJobStatusNotification(
                    $order,
                    $stage,
                    $note,
                    Setting::where('user_id', $order->userId)->value('name'),
                );
                $notification->id = (string) Str::uuid();
                $customer->notify($notification);

                $delivery->update([
                    'customer_id' => $customer->id,
                    'status' => 'sent',
                    'attempt_count' => $delivery->attempt_count + 1,
                    'notification_id' => $notification->id,
                    'last_error' => null,
                    'last_attempted_at' => now(),
                    'sent_at' => now(),
                ]);

                return $delivery->fresh();
            }, 3);
        } catch (Throwable $exception) {
            Log::warning('Customer lifecycle notification failed.', [
                'order_id' => $order->id,
                'stage' => $stage,
                'exception' => $exception->getMessage(),
            ]);

            $delivery = OrderNotificationDelivery::firstOrCreate(
                ['order_id' => $order->id, 'stage' => $stage, 'channel' => 'database'],
                ['user_id' => $order->userId, 'customer_id' => $customer->id],
            );
            $delivery->increment('attempt_count');
            $delivery->update([
                'customer_id' => $customer->id,
                'status' => 'failed',
                'last_error' => Str::limit($exception->getMessage(), 2000),
                'last_attempted_at' => now(),
            ]);

            return $delivery->fresh();
        }
    }
}
