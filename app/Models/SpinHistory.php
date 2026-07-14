<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpinHistory extends Model
{
    use HasFactory;
    protected $table = 'spin_histories';
    protected $fillable = [
        'user_id',
        'spin_prize_id'
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function spinPrize()
    {
        return $this->belongsTo(SpinPrice::class, 'spin_prize_id');
    }
}
