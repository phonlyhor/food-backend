<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupons extends Model
{
    use HasFactory;
    protected $table = 'coupons';
    protected $fillable = [
        'code',
        'name',
        'type',
        'discount_value',
        'minimum_order',
        'usage_limit',
        'used_count',
        'start_date',
        'end_date',
        'status',
    ];
    
}
