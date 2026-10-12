<?php

namespace Platform\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Illuminate\Support\Facades\Log;
use Platform\ActivityLog\Traits\LogsActivity;
use Platform\Crm\Traits\HasCompanyLinksTrait;
use Platform\Crm\Models\CrmCompany;

class SalesBoard extends Model
{
    use LogsActivity, HasCompanyLinksTrait;

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

    /**
     * Partner-Company dieses Boards (EFP-Modell: ein Vertriebsboard pro Partner).
     *
     * Umgesetzt über das polymorphe CRM-Link-Pattern (crm_company_links) statt
     * eines harten FKs – damit bleibt das Sales-Schema unabhängig vom CRM-Schema.
     * Ein Board hat genau einen Partner: die (einzige) verknüpfte Company.
     */
    public function partner(): ?CrmCompany
    {
        return $this->companies()->first();
    }

    /**
     * Setzt die Partner-Company (ersetzt eine ggf. vorhandene Verknüpfung).
     */
    public function setPartner(?CrmCompany $company): void
    {
        $this->detachAllCompanies();
        if ($company) {
            $this->attachCompany($company);
        }
    }
}
