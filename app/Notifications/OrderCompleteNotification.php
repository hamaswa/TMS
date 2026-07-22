<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;


class OrderCompleteNotification extends Notification
{
    use Queueable;
    public $order;
    public $setting;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($order,$setting)
    {
        $this->order = $order;
        $this->setting = $setting;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }


    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
   public function toArray($notifiable)
    {
        $firstTransaction = $this->order->transactions->first(); // Get the first transaction

       return [
            'Shop_Name' => $this->setting->name,
            'order_id' => $this->order->id,
            'Order_Type' => 'Tailoring',
            'customers' => [
                'name' => $this->order->customers->name, // Accessing customer name via relationship
            ],
            'suitQuantity' => $this->order->suitQuantity,
            'suitNumber' => $this->order->suitNum,
            'returnDate' => $this->order->returnDate,
            'Design' => $this->order->design,
            'DesignPrice' => $this->order->designPrice,
            'total_payment' => $this->order->totalPayment,
            'Payment_Paid' => $firstTransaction ? $firstTransaction->recivedPayment : 0, // Default to 0 if no transaction
            'Remaining_Payment' => $firstTransaction ? $firstTransaction->remainingBalance : 0, // Default to 0 if no transaction
            'status' => $this->order->status,
            'message' => 'Your order has been marked as completed.',
        ];
    }
}
