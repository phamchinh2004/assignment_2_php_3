<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'frozen_order_id',
        'status_id',
        'notes',
        'changed_by',
    ];

    /**
     * Lấy frozen order của status order này
     */
    public function frozenOrder()
    {
        return $this->belongsTo(Frozen_order::class, 'frozen_order_id');
    }

    /**
     * Lấy trạng thái của status order này
     */
    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * Lấy người thay đổi trạng thái
     */
    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
