<?php

namespace Platform\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Illuminate\Support\Facades\Log;

class SalesBoard extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'description',
        'order',
        'user_id',
        'team_id',
    ];

    protected $casts = [
        'uuid' => 'string',
    ];

    protected static function booted(): void
    {
        Log::info('SalesBoard Model: booted() called!');
        
        static::creating(function (self $model) {
            
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $model->uuid = $uuid;
        });
    }

    public function deals(): HasMany
    {
        return $this->hasMany(SalesDeal::class, 'sales_board_id');
    }

    public function slots(): HasMany
    {
        return $this->hasMany(SalesBoardSlot::class, 'sales_board_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SalesBoardTemplate::class, 'sales_board_template_id');
    }



    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }
}
