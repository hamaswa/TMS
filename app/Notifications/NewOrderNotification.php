<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification
{
    use Queueable;

    public $obj;
    public $transaction;
    public $setting;

    /**
     * Create a new notification instance.
     *
     * @param  mixed  $order
     * @return void
     */
    public function __construct($obj,$transaction,$setting)
    {
        $this->obj = $obj;
        $this->transaction = $transaction;
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
        return ['database']; // You can also add other channels like 'mail', 'broadcast', etc.
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
       return [
            'Shop_Name' => $this->setting->name,
            'Order_Type' => 'Tailoring',
            'order_id' => $this->obj->id,
            'customers' => [
                'name' => $this->obj->customers->name, // Accessing customer name via relationship
            ],
            'suitQuantity' => $this->obj->suitQuantity,
            'serialNumber' => $this->obj->suitNum,
            'orderDate' => $this->obj->created_at,
            'returnDate' => $this->obj->returnDate,
            'Design' => $this->obj->design,
            'total_payment' => $this->obj->totalPayment,
            'Payment_Paid' => $this->transaction->recivedPayment,
            'Remaining_Payment' => $this->transaction->remainingBalance,
            'message' => 'Your order has been created.',
            'status' => $this->obj->status,
        ];

    }
}
