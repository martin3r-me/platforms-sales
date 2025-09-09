<?php

namespace Platform\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Illuminate\Support\Facades\Log;

class SalesBoardTemplateSlot extends Model
{
    protected $fillable = [
        'uuid',
        'sales_board_template_id',
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
        Log::info('SalesBoardTemplateSlot Model: booted() called!');
        
        static::creating(function (self $model) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $model->uuid = $uuid;
        });
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SalesBoardTemplate::class, 'sales_board_template_id');
    }
}
