<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner_image extends Model
{
    use HasFactory;
    protected $fillable = [
        'path',
        'banner_id'
    ];
    public function banner()
    {
        return $this->belongsTo(Banner::class, 'banner_id');
    }
}
