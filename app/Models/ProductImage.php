<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $fillable = ['product_id', 'path', 'alt', 'sort_order'];

    // If you store full URLs or relative storage paths, you may add helpers.
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
