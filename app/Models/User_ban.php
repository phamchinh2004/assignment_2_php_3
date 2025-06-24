<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_ban extends Model
{
    use HasFactory;
    protected $fillable = [
        'reason',
        'user_id',
        'status',
        'is_active'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
