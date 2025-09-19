<?php

namespace Platform\Sales\Models;

// Lookup-Tabellen werden über separate Models verwaltet
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Symfony\Component\Uid\UuidV7;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;

use Platform\ActivityLog\Traits\LogsActivity;

class SalesDeal extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid',
        'user_id',
        'user_in_charge_id',
        'team_id',
        'title',
        'description',
        'due_date',
        'deal_value',
        'probability_percent',
        'deal_source',
        'deal_type',

        'is_done',
        'order',
        'slot_order',
        'sales_board_id',
        'sales_board_slot_id',
    ];

    protected $casts = [
        'due_date' => 'date',
        'deal_value' => 'decimal:2',
        'probability_percent' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $model->uuid = $uuid;

            if (! $model->user_id) {
                $model->user_id = Auth::id();
            }

            if (! $model->team_id) {
                $model->team_id = Auth::user()->currentTeam->id;
            }
        });
    }

    public function setUserInChargeIdAttribute($value)
    {
        $this->attributes['user_in_charge_id'] = empty($value) || $value === 'null' ? null : (int)$value;
    }

    public function setDueDateAttribute($value)
    {
        $this->attributes['due_date'] = empty($value) || $value === 'null' ? null : $value;
    }

    public function setProbabilityPercentAttribute($value)
    {
        $this->attributes['probability_percent'] = empty($value) || $value === '' || $value === 'null' ? null : (int)$value;
    }

    public function setDealValueAttribute($value)
    {
        $this->attributes['deal_value'] = empty($value) || $value === '' || $value === 'null' ? null : $value;
    }

    public function user()
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function team()
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }

    public function salesBoard()
    {
        return $this->belongsTo(SalesBoard::class, 'sales_board_id');
    }

    public function salesBoardSlot()
    {
        return $this->belongsTo(SalesBoardSlot::class, 'sales_board_slot_id');
    }

    public function userInCharge()
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'user_in_charge_id');
    }

    // Vertriebsspezifische Methoden
    public function getExpectedValueAttribute(): float
    {
        if (!$this->deal_value || !$this->probability_percent) {
            return 0;
        }
        return ($this->deal_value * $this->probability_percent) / 100;
    }

    public function isHighValue(): bool
    {
        return $this->deal_value && $this->deal_value > 10000; // > 10k EUR
    }

    public function isHot(): bool
    {
        return $this->probability_percent && $this->probability_percent >= 80;
    }
}
