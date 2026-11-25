<?php

namespace App\Events;

use App\Models\CustomerSupport;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class CustomerSupportReplyEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $customer_support;
    public $reply_message;

    public function __construct(CustomerSupport $customerSupport, $replyMessage)
    {
        $this->customer_support = $customerSupport;
        $this->reply_message = $replyMessage;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}