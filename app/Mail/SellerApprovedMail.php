<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue; // implement to queue
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class SellerApprovedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $setPasswordUrl;
    public $sellerType;

    public function __construct(User $user, string $setPasswordUrl, string $sellerType = 'one_time')
    {
        $this->user = $user;
        $this->setPasswordUrl = $setPasswordUrl;
        $this->sellerType = $sellerType;
        // optional: set queue name
        $this->onQueue('emails');
    }

    public function build()
    {
        return $this
            ->subject('Your seller account has been approved')
            ->view('emails.seller-approved') // blade view
            ->with([
                'user' => $this->user,
                'setPasswordUrl' => $this->setPasswordUrl,
                'sellerType' => $this->sellerType,
            ]);
    }
}
