<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $fillable = [
        'name',
        'code',
        'image'
    ];
    public function sectionLanguages()
    {
        return $this->hasMany(SectionLanguage::class);
    }
}
