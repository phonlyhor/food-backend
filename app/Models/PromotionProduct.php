<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionProduct extends Model
{
    use HasFactory;
    protected $table = 'promotion_products';
    protected $fillable = [
        'promotion_id',
        'product_id',
    ];
    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }
    public function product(){
        
        return $this->belongsTo(Product::class);
    }
}
