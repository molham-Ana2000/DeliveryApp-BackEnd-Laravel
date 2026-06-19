<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewOrderNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order->loadMissing([
            'customer',
            'items',
            'restaurant',
        ]);
    }

    public function build(): self
    {
        return $this->subject("Neue Bestellung #{$this->order->order_number} erhalten")
            ->view('emails.new_order_notification')
            ->with([
                'order' => $this->order,
                'customerName' => $this->order->customer->first_name . ' ' . $this->order->customer->last_name,
                'restaurantName' => $this->order->restaurant->name ?? 'Unbekanntes Restaurant',
                'items' => $this->order->items,
                'total' => $this->order->order_total,
            ]);
    }
}