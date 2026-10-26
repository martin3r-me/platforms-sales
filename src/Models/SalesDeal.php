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
use Platform\Integrations\Traits\HasLexwareQuotationLinksTrait;
use Platform\Integrations\Contracts\LexwareQuotationLinkableInterface;

class SalesDeal extends Model implements LexwareQuotationLinkableInterface
{
    use HasFactory, SoftDeletes, LogsActivity, HasCompanyLinksTrait, HasContactLinksTrait, HasLexwareQuotationLinksTrait;

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
        'lost_at',
        'lost_reason',
        'on_hold_at',
        'lost_reason_code',
    ];

    /**
     * Verfügbare Verlustgründe als [code => name] – datengetrieben aus dem
     * Lookup sales_lost_reasons (kein Hardcode). Nur aktive Gründe.
     */
    public static function lostReasonOptions(): array
    {
        return SalesLostReason::active()->ordered()->pluck('name', 'code')->toArray();
    }

    /**
     * Prüft, ob ein Verlustgrund-Code existiert und aktiv ist.
     */
    public static function isValidLostReasonCode(?string $code): bool
    {
        if ($code === null || $code === '') {
            return false;
        }

        return SalesLostReason::where('code', $code)->where('is_active', true)->exists();
    }

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
        'done_at' => 'datetime',
        'lost_at' => 'datetime',
        'on_hold_at' => 'datetime',
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

    // --- Status Helpers ---

    public function isWon(): bool
    {
        return (bool) $this->is_done;
    }

    public function isLost(): bool
    {
        return $this->lost_at !== null;
    }

    public function isOpen(): bool
    {
        return !$this->isWon() && !$this->isLost();
    }

    public function isOnHold(): bool
    {
        return $this->on_hold_at !== null && $this->isOpen();
    }

    /**
     * Aktiv in der Pipeline: offen und NICHT pausiert.
     */
    public function isOngoing(): bool
    {
        return $this->isOpen() && !$this->isOnHold();
    }

    /**
     * Lebenszyklus-Status des Deals: open | on_hold | won | lost.
     */
    public function status(): string
    {
        if ($this->isWon()) {
            return 'won';
        }

        if ($this->isLost()) {
            return 'lost';
        }

        return $this->isOnHold() ? 'on_hold' : 'open';
    }

    /**
     * Sprechendes Status-Label für die UI.
     */
    public function statusLabel(): string
    {
        return match ($this->status()) {
            'won'     => 'Gewonnen',
            'lost'    => 'Verloren',
            'on_hold' => 'Pausiert',
            default   => 'Offen',
        };
    }

    // --- Status Actions ---

    public function markAsWon(): self
    {
        $this->is_done = true;
        $this->done_at = now();
        $this->lost_at = null;
        $this->lost_reason = null;
        $this->lost_reason_code = null;
        $this->on_hold_at = null;
        $this->save();

        return $this;
    }

    public function markAsLost(?string $reason = null, ?string $reasonCode = null): self
    {
        $this->lost_at = now();
        $this->lost_reason = $reason;
        $this->lost_reason_code = self::isValidLostReasonCode($reasonCode) ? $reasonCode : null;
        $this->is_done = false;
        $this->done_at = null;
        $this->on_hold_at = null;
        $this->sales_board_slot_id = null;
        $this->save();

        return $this;
    }

    /**
     * Deal pausieren (On hold). Bleibt offen, verschwindet aber aus der aktiven Tageslinse.
     */
    public function markAsOnHold(): self
    {
        $this->on_hold_at = now();
        $this->is_done = false;
        $this->done_at = null;
        $this->lost_at = null;
        $this->lost_reason = null;
        $this->lost_reason_code = null;
        $this->save();

        return $this;
    }

    /**
     * Pause aufheben – Deal wird wieder aktiv (Ongoing).
     */
    public function resumeFromHold(): self
    {
        $this->on_hold_at = null;
        $this->save();

        return $this;
    }

    public function reopenDeal(): self
    {
        $this->is_done = false;
        $this->done_at = null;
        $this->lost_at = null;
        $this->lost_reason = null;
        $this->lost_reason_code = null;
        $this->on_hold_at = null;
        $this->save();

        return $this;
    }

    // --- Query Scopes ---

    public function scopeOpen($query)
    {
        return $query->where('is_done', false)->whereNull('lost_at');
    }

    public function scopeWon($query)
    {
        return $query->where('is_done', true);
    }

    public function scopeLost($query)
    {
        return $query->whereNotNull('lost_at');
    }

    /**
     * Pausierte Deals (offen + on_hold_at gesetzt).
     */
    public function scopeOnHold($query)
    {
        return $query->where('is_done', false)
            ->whereNull('lost_at')
            ->whereNotNull('on_hold_at');
    }

    /**
     * Aktiv in der Pipeline: offen und NICHT pausiert.
     */
    public function scopeOngoing($query)
    {
        return $query->where('is_done', false)
            ->whereNull('lost_at')
            ->whereNull('on_hold_at');
    }

    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
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

    // LexwareQuotationLinkableInterface
    public function getLexwareQuotationLinkableId(): int
    {
        return $this->id;
    }

    public function getLexwareQuotationLinkableType(): string
    {
        return static::class;
    }

    public function getTeamId(): int
    {
        return $this->team_id;
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

    /**
     * Abnehmer als Organisation-Entity (optional, zusätzlich zur CRM-Company).
     *
     * Der Deal ist die Beziehung Partner (Board-Entity) ↔ Abnehmer. Strategisch
     * getrackte Abnehmer (Großhändler/Key-Accounts) hängen zusätzlich als Org-Entity
     * am Deal (DimensionLink dimension=entity), damit sie im VSM/Org-Modell auftauchen.
     * Organization ist optionale Abhängigkeit (class_exists-Guard).
     */
    public function abnehmerEntity()
    {
        if (! class_exists(\Platform\Organization\Services\EntityDimensionBridge::class)) {
            return null;
        }

        return \Platform\Organization\Services\EntityDimensionBridge::linksForLinkables(
            ['sales_deal'],
            [$this->id]
        )->first()?->entity;
    }

    /**
     * Setzt/ersetzt die Abnehmer-Entity dieses Deals (null entfernt die Verknüpfung).
     */
    public function setAbnehmerEntity(?int $entityId): void
    {
        if (! class_exists(\Platform\Organization\Services\EntityDimensionBridge::class)) {
            return;
        }

        $bridge = \Platform\Organization\Services\EntityDimensionBridge::class;

        if ($entityId) {
            $bridge::replaceLinks('sales_deal', $this->id, $entityId, [
                'team_id' => $this->team_id,
                'created_by_user_id' => auth()->id(),
            ]);
            return;
        }

        foreach ($bridge::linksForLinkables(['sales_deal'], [$this->id]) as $link) {
            if ($link->entity_id) {
                $bridge::deleteLink($link->entity_id, 'sales_deal', $this->id);
            }
        }
    }

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
