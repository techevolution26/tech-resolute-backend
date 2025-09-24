<?php
namespace App\Jobs;
use App\Models\Order;
use App\Mail\NewOrderMail;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;

class SendOrderNotifications implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public Order $order) {}
    public function handle() {
        // send admin email
        Mail::to(config('mail.admin_email', 'admin@yourdomain.com'))->queue(new NewOrderMail($this->order));

        // send to seller if product has seller with contact email
        if ($seller = $this->order->product->seller) {
            if ($seller->contact_email) {
                Mail::to($seller->contact_email)->queue(new NewOrderMail($this->order, $recipientIsSeller=true));
            }
        }

    }
}
