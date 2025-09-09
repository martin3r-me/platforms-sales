<?php

namespace Platform\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Illuminate\Support\Facades\Log;

class SalesBoardTemplate extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'description',
        'is_default',
        'is_system',
        'user_id',
        'team_id',
    ];

    protected $casts = [
        'uuid' => 'string',
        'is_default' => 'boolean',
        'is_system' => 'boolean',
    ];

    protected static function booted(): void
    {
        Log::info('SalesBoardTemplate Model: booted() called!');
        
        static::creating(function (self $model) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $model->uuid = $uuid;
        });
    }

    public function slots(): HasMany
    {
        return $this->hasMany(SalesBoardTemplateSlot::class, 'sales_board_template_id');
    }

    public function boards(): HasMany
    {
        return $this->hasMany(SalesBoard::class, 'sales_board_template_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }

    /**
     * Erstellt ein neues Board basierend auf diesem Template
     */
    public function createBoard(array $boardData = []): SalesBoard
    {
        $board = new SalesBoard();
        $board->name = $boardData['name'] ?? $this->name . ' - ' . now()->format('d.m.Y');
        $board->description = $boardData['description'] ?? $this->description;
        $board->sales_board_template_id = $this->id;
        $board->user_id = auth()->id();
        $board->team_id = auth()->user()->currentTeam->id;
        $board->save();

        // Slots aus Template kopieren
        foreach ($this->slots()->orderBy('order')->get() as $templateSlot) {
            $slot = new SalesBoardSlot();
            $slot->sales_board_id = $board->id;
            $slot->name = $templateSlot->name;
            $slot->description = $templateSlot->description;
            $slot->color = $templateSlot->color;
            $slot->order = $templateSlot->order;
            $slot->save();
        }

        return $board;
    }
}
