<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $table = 'products';
    protected $fillable = [
        'category_id',
        'name',
        'description',
        'image',
        'status'
    ];
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function productVariants()
    {
        return $this->hasMany(Product_Varaints::class);
    }
    public function promotions()
    {
        return $this->belongsToMany(Promotion::class, 'promotion_products', 'product_id', 'promotion_id');
    }
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }
}
