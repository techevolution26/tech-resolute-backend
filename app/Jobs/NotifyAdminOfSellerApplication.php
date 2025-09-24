<?php
namespace App\Jobs;
use App\Models\SellerApplication;
use App\Mail\SellerApplicationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;

class NotifyAdminOfSellerApplication implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public SellerApplication $application) {}
    public function handle() {
        Mail::to(config('mail.admin_email','admin@yourdomain.com'))->queue(new SellerApplicationMail($this->application));
    }
}
