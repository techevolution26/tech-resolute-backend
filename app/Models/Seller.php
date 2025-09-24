<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Seller extends Model
{
    use HasFactory;

   protected $fillable = [
        'business_name',
        'contact_name',
        'contact_email',
        'phone',
        'website',
        'country',
        'logo_path',   // or logo_url depending on migration
        'message',
        'approved',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'approved' => 'boolean',
    ];
}
