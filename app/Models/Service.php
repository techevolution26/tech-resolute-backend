<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Service extends Model {
    protected $fillable = ['slug','title','summary','features','starting_price','description'];
    protected $casts = ['features' => 'array'];
}
