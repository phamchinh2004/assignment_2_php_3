<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_spin_progress extends Model
{
    use HasFactory;
    protected $fillable = [
        'current_spin',
        'user_id',
        'rank_id',
    ];
    public function rank()
    {
        return $this->belongsTo(Rank::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
