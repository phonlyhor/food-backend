<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product_Varaints extends Model
{
    use HasFactory;
    protected $table = 'product_varaints';
    protected $fillable = [
        'product_id',
        'size',
        'price',
        'stock',
        
    ];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
