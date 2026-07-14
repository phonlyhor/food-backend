<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpinPrice extends Model
{
    use HasFactory;
    protected $table = 'spin_prizes';
    protected $fillable = [
        'name',
        'prize_type',
        'prize_value',
        'chance',
        'quantity',
        'is_active'
    ];

    public function spinHistories()
    {
        return $this->hasMany(SpinHistory::class, 'spin_prize_id');
    }
}
