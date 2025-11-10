<?php
namespace App\Notifications;

use App\Models\SellerApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SellerApplicationReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public SellerApplication $application;

    public function __construct(SellerApplication $application)
    {
        $this->application = $application;
        $this->onQueue('emails'); // optional: keep email work on "emails" queue
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('New seller application received')
            ->greeting('Hello admin,')
            ->line("A new seller application was submitted by: {$this->application->contact_name} ({$this->application->email}).")
            ->line('Business: ' . ($this->application->business_name ?? '—'))
            ->action('View application', url("/admin/seller-applications/{$this->application->id}"))
            ->line('Please review and approve or reject the application in the admin console.');
    }

    public function toDatabase($notifiable)
    {
        return [
            'application_id' => $this->application->id,
            'contact_name' => $this->application->contact_name,
            'email' => $this->application->email,
            'business_name' => $this->application->business_name,
            'submitted_at' => $this->application->created_at?->toDateTimeString(),
        ];
    }
}
