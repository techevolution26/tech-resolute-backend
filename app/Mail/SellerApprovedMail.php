<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SellerApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $setPasswordUrl;
    public $sellerType;

    public function __construct($user, string $setPasswordUrl, ?string $sellerType = null)
    {
        $this->user = $user;
        $this->setPasswordUrl = $setPasswordUrl;
        $this->sellerType = $sellerType;
    }

    public function build()
    {
        return $this
            ->subject('Your seller account is ready')
            ->view('emails.seller-approved') // create a simple view or use ->text(...)
            ->with([
                'name' => $this->user->name ?? $this->user->email,
                'setPasswordUrl' => $this->setPasswordUrl,
                'sellerType' => $this->sellerType,
            ]);
    }
}
