<?php

namespace Platform\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Illuminate\Support\Facades\Log;

class SalesBoardSlot extends Model
{
    protected $fillable = [
        'uuid',
        'sales_board_id',
        'name',
        'description',
        'color',
        'order',
    ];

    protected $casts = [
        'uuid' => 'string',
    ];

    protected static function booted(): void
    {
        Log::info('SalesBoardSlot Model: booted() called!');
        
        static::creating(function (self $model) {
            
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $model->uuid = $uuid;
        });
    }

    public function deals(): HasMany
    {
        return $this->hasMany(SalesDeal::class, 'sales_board_slot_id');
    }

    public function salesBoard(): BelongsTo
    {
        return $this->belongsTo(SalesBoard::class, 'sales_board_id');
    }
}
