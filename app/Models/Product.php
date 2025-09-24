<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Seller;
use App\Models\User;
use App\Models\Category;
use App\Models\ProductImage;

class Product extends Model {
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'seller_id', 'user_id', 'category_id', 'slug', 'title', 'description',
        'price', 'currency', 'condition', 'stock', 'image_url', 'status', 'sku', 'meta',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'meta' => 'array',
    ];

    public function seller() {
        return $this->belongsTo( Seller::class );
    }

  public function images(){

    return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }


    public function creator() {
        return $this->belongsTo( User::class, 'user_id' );
    }

    public function category() {
        return $this->belongsTo( Category::class );
    }
}

