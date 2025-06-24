<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_manager_setting extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'manager_setting_id',
        'is_active'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function manager_setting()
    {
        return $this->belongsTo(Manager_setting::class);
    }
}
