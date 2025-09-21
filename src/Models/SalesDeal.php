<?php

namespace Platform\Sales\Models;

// Lookup-Tabellen werden über separate Models verwaltet
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Symfony\Component\Uid\UuidV7;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Platform\ActivityLog\Traits\LogsActivity;
use Platform\Crm\Traits\HasCompanyLinksTrait;
use Platform\Crm\Traits\HasContactLinksTrait;

class SalesDeal extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, HasCompanyLinksTrait, HasContactLinksTrait;

    protected $fillable = [
        'uuid',
        'user_id',
        'user_in_charge_id',
        'team_id',
        'title',
        'description',
        'notes',
        'due_date',
        'close_date',
        'deal_value',
        'billing_interval',
        'billing_duration_months',
        'monthly_recurring_value',
        'expected_value',
        'minimum_value',
        'maximum_value',
        'probability_percent',
        'deal_source',
        'deal_type',
        'competitor',
        'next_step',
        'next_step_date',

        'is_done',
        'is_hot',
        'is_starred',
        'order',
        'slot_order',
        'sales_board_id',
        'sales_board_slot_id',
        'sales_priority_id',
        'sales_deal_source_id',
        'sales_deal_type_id',
    ];

    protected $casts = [
        'due_date' => 'date',
        'close_date' => 'date',
        'next_step_date' => 'date',
        'deal_value' => 'decimal:2',
        'monthly_recurring_value' => 'decimal:2',
        'expected_value' => 'decimal:2',
        'minimum_value' => 'decimal:2',
        'maximum_value' => 'decimal:2',
        'probability_percent' => 'integer',
        'is_done' => 'boolean',
        'is_hot' => 'boolean',
        'is_starred' => 'boolean',
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

    public function setExpectedValueAttribute($value)
    {
        $this->attributes['expected_value'] = empty($value) || $value === '' || $value === 'null' ? null : $value;
    }

    public function setMinimumValueAttribute($value)
    {
        $this->attributes['minimum_value'] = empty($value) || $value === '' || $value === 'null' ? null : $value;
    }

    public function setMaximumValueAttribute($value)
    {
        $this->attributes['maximum_value'] = empty($value) || $value === '' || $value === 'null' ? null : $value;
    }

    public function setCloseDateAttribute($value)
    {
        $this->attributes['close_date'] = empty($value) || $value === 'null' ? null : $value;
    }

    public function setNextStepDateAttribute($value)
    {
        $this->attributes['next_step_date'] = empty($value) || $value === 'null' ? null : $value;
    }

    public function setBillingDurationMonthsAttribute($value)
    {
        $this->attributes['billing_duration_months'] = empty($value) || $value === '' || $value === 'null' ? null : (int)$value;
    }

    public function setMonthlyRecurringValueAttribute($value)
    {
        $this->attributes['monthly_recurring_value'] = empty($value) || $value === '' || $value === 'null' ? null : $value;
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

    public function priority()
    {
        return $this->belongsTo(SalesPriority::class, 'sales_priority_id');
    }

    public function dealSource()
    {
        return $this->belongsTo(SalesDealSource::class, 'sales_deal_source_id');
    }

    public function dealType()
    {
        return $this->belongsTo(SalesDealType::class, 'sales_deal_type_id');
    }

    public function billables(): HasMany
    {
        return $this->hasMany(SalesDealBillable::class, 'sales_deal_id')->orderBy('order');
    }

    public function activeBillables(): HasMany
    {
        return $this->hasMany(SalesDealBillable::class, 'sales_deal_id')->where('is_active', true)->orderBy('order');
    }

    /**
     * CRM Integration - Companies and Contacts are now available via traits:
     * - $deal->companies() - Collection of linked companies
     * - $deal->contacts() - Collection of linked contacts
     * - $deal->attachCompany($company) - Link a company
     * - $deal->attachContact($contact) - Link a contact
     * - $deal->detachCompany($company) - Unlink a company
     * - $deal->detachContact($contact) - Unlink a contact
     */

    // Vertriebsspezifische Methoden
    public function getCalculatedExpectedValueAttribute(): float
    {
        if (!$this->deal_value || !$this->probability_percent) {
            return 0;
        }
        return ($this->deal_value * $this->probability_percent) / 100;
    }

    public function getExpectedValueAttribute($value): float
    {
        // Wenn expected_value in der DB gesetzt ist, verwende das
        if ($value !== null && $value !== '') {
            return (float) $value;
        }
        
        // Sonst berechne es aus deal_value und probability_percent
        return $this->getCalculatedExpectedValueAttribute();
    }

    public function isHighValue(): bool
    {
        return $this->deal_value && $this->deal_value > 10000; // > 10k EUR
    }

    public function isHot(): bool
    {
        return $this->is_hot || ($this->probability_percent && $this->probability_percent >= 80);
    }

    // Billables-basierte Berechnungen
    public function calculateTotalValueFromBillables(): float
    {
        return $this->activeBillables->sum('total_value');
    }

    public function calculateExpectedValueFromBillables(): float
    {
        return $this->activeBillables->sum('expected_value');
    }

    public function calculateWeightedProbabilityFromBillables(): float
    {
        if (!$this->hasBillables()) {
            return $this->probability_percent ?? 0;
        }
        
        $totalValue = $this->calculateTotalValueFromBillables();
        if ($totalValue == 0) return 0;
        
        $weightedSum = $this->activeBillables->reduce(function($carry, $billable) {
            return $carry + ($billable->probability_percent * $billable->total_value);
        }, 0);
        
        return round($weightedSum / $totalValue, 1);
    }

    public function updateDealValueFromBillables(): void
    {
        $calculatedValue = $this->calculateTotalValueFromBillables();
        if ($this->deal_value != $calculatedValue) {
            $this->deal_value = $calculatedValue;
            $this->save();
        }
        
        // Aktualisiere auch die Deal-Wahrscheinlichkeit
        $this->updateDealProbabilityFromBillables();
    }

    public function updateDealProbabilityFromBillables(): void
    {
        if ($this->hasBillables()) {
            $calculatedProbability = $this->calculateWeightedProbabilityFromBillables();
            if ($this->probability_percent != $calculatedProbability) {
                $this->probability_percent = $calculatedProbability;
                $this->save();
            }
        }
    }

    public function hasBillables(): bool
    {
        return $this->billables()->count() > 0;
    }

    public function getBillablesTotalAttribute(): float
    {
        return $this->calculateTotalValueFromBillables();
    }

    public function getBillablesExpectedTotalAttribute(): float
    {
        return $this->calculateExpectedValueFromBillables();
    }

    public function getCalculatedProbabilityAttribute(): float
    {
        if ($this->hasBillables()) {
            return $this->calculateWeightedProbabilityFromBillables();
        }
        return $this->probability_percent ?? 0;
    }
}
