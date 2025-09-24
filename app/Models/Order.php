<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Order extends Model {
    protected $fillable = ['product_id','customer_name','customer_email','customer_phone','shipping_address','quantity','total','message','status','metadata'];
    protected $casts = ['metadata' => 'array'];

    public function product(): BelongsTo {
        return $this->belongsTo(Product::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
