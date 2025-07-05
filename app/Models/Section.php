<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'code',
        'content',
        'status'
    ];
    public function sectionLanguages()
    {
        return $this->hasMany(SectionLanguage::class);
    }
    public function getTranslatedContent($locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        return $this->sectionLanguages()
            ->whereHas('language', fn($q) => $q->where('code', $locale))
            ->first()?->content ?? $this->content;
    }
}
