<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SellerApplication extends Model {
 protected $fillable = [
        'application_type',
        'business_name',
        'contact_name',
        'email',
        'phone',
        'website',
        'message',
        'logo_url',
        'items',
        'notes',
        'status',
        'approved_at',
    ];

    protected $casts = [
        'items' => 'array',
        'approved_at' => 'datetime',
    ];}
