<?php

namespace App\Listeners;

use App\Events\CustomerSupportReplyEvent;
use App\Http\Controllers\Merchant\emailTemplateController;

class CustomerSupportReplyListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  CustomerSupportReplyEvent  $data
     * @return void
     */
    public function handle(CustomerSupportReplyEvent $data)
    {
        $email_listener = new emailTemplateController();
        $email_listener->CustomerSupportReplySendEmail($data->customer_support, $data->reply_message);
    }
}