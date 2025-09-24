<?php
namespace App\Mail;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewOrderMail extends Mailable {
    use Queueable, SerializesModels;
    public $order;

    public function __construct( Order $order ) {
        $this->order = $order;
    }

    public function build() {
        return $this->subject( "New order #{$this->order->id}" )
        ->view( 'emails.orders.new' ) // create a Blade view accordingly
        ->with( [ 'order' => $this->order ] );
    }
}
