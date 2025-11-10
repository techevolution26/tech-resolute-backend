<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\User;

class SellerApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public User $user;
    public string $setPasswordUrl;
    public string $sellerType;

    public function __construct(User $user, string $setPasswordUrl, string $sellerType = 'one_time')
    {
        $this->user = $user;
        $this->setPasswordUrl = $setPasswordUrl;
        $this->sellerType = $sellerType;
        $this->onQueue('emails');
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your seller account has been approved')
            ->greeting("Hello {$this->user->name},")
            ->line("Good news — your seller application has been approved.")
            ->line("Seller type: {$this->sellerType}")
            ->action('Set your password', $this->setPasswordUrl)
            ->line('After setting your password you can access the seller dashboard to manage products and orders.');
    }

    public function toDatabase($notifiable)
    {
        return [
            'user_id' => $this->user->id,
            'seller_type' => $this->sellerType,
            'message' => 'Your seller application was approved.',
        ];
    }
}
