<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectionLanguage extends Model
{
    protected $fillable = [
        'section_id',
        'language_id',
        'content'
    ];
    public function section()
    {
        return $this->belongsTo(Section::class);
    }
    public function language()
    {
        return $this->belongsTo(Language::class);
    }
}
