<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpinQuotas extends Model
{
    use HasFactory;
    protected $table = 'user_spin_quotas';
    protected $primaryKey = null;
    public $incrementing = false;
    protected $fillable = [
        'user_id',
        'spin_count',
        'date'
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
