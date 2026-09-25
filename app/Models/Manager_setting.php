<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manager_setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'manager_name',
        'manager_code',
        'parent_manager_setting_id',
    ];

    public function manager()
    {
        return $this->belongsTo(Manager_setting::class, 'parent_manager_setting_id');
    }

    public function managers()
    {
        return $this->children();
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_manager_setting_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_manager_setting_id')->orderBy('id');
    }

    public function user_manager_settings()
    {
        return $this->hasMany(User_manager_setting::class);
    }
}
