<?php

namespace Platform\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\ActivityLog\Traits\LogsActivity;

class SalesDealBillable extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid',
        'sales_deal_id',
        'name',
        'description',
        'amount',
        'billing_type',
        'billing_interval',
        'duration_months',
        'start_date',
        'end_date',
        'total_value',
        'order',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'total_value' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $model->uuid = $uuid;
        });

        static::saving(function (self $model) {
            // Berechne total_value automatisch
            if ($model->billing_type === 'recurring' && $model->duration_months) {
                $model->total_value = (float) $model->amount * (int) $model->duration_months;
            } else {
                $model->total_value = (float) $model->amount;
            }
        });

        static::saved(function (self $model) {
            // Aktualisiere den Deal-Wert, wenn sich ein Billable ändert
            $model->salesDeal?->updateDealValueFromBillables();
        });

        static::deleted(function (self $model) {
            // Aktualisiere den Deal-Wert, wenn ein Billable gelöscht wird
            $model->salesDeal?->updateDealValueFromBillables();
        });
    }

    public function salesDeal(): BelongsTo
    {
        return $this->belongsTo(SalesDeal::class);
    }

    public function isRecurring(): bool
    {
        return $this->billing_type === 'recurring';
    }

    public function isOneTime(): bool
    {
        return $this->billing_type === 'one_time';
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format((float) $this->amount, 2, ',', '.') . ' €';
    }

    public function getFormattedTotalValueAttribute(): string
    {
        return number_format((float) $this->total_value, 2, ',', '.') . ' €';
    }

    public function getBillingDescriptionAttribute(): string
    {
        if ($this->isRecurring()) {
            $interval = match($this->billing_interval) {
                'monthly' => 'monatlich',
                'quarterly' => 'vierteljährlich',
                'yearly' => 'jährlich',
                default => $this->billing_interval
            };
            
            return "{$this->formatted_amount} {$interval} × {$this->duration_months} Monate = {$this->formatted_total_value}";
        }
        
        return $this->formatted_amount . ' (einmalig)';
    }
}
