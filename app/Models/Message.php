<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'message',
        'type',
        'image_path',
        'is_read'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // Accessor để lấy URL đầy đủ của ảnh
    public function getImageUrlAttribute()
    {
        if ($this->image_path) {
            return Storage::url($this->image_path);
        }
        return null;
    }

    // Kiểm tra xem tin nhắn có phải là ảnh không
    public function isImage()
    {
        return $this->type === 'image';
    }

    // Kiểm tra xem tin nhắn có phải là text không
    public function isText()
    {
        return $this->type === 'text';
    }

    // Scope để lấy tin nhắn theo loại
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Scope để lấy tin nhắn có ảnh
    public function scopeWithImages($query)
    {
        return $query->where('type', 'image')->whereNotNull('image_path');
    }

    // Scope để lấy tin nhắn text
    public function scopeTextOnly($query)
    {
        return $query->where('type', 'text');
    }
}