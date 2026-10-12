<?php

namespace Platform\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Illuminate\Support\Facades\Log;
use Platform\ActivityLog\Traits\LogsActivity;

class SalesBoard extends Model
{
    use LogsActivity;

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
     * Partner dieses Boards als Organisation-Entity (EFP-Modell: ein Board pro Partner).
     *
     * Umgesetzt wie Planner-Projekte: das Board hängt über einen DimensionLink
     * (dimension=entity) an der Org-Entity. Kein harter FK, kein CRM-Schema-Bezug –
     * das Organization-Modul ist optionale Abhängigkeit (class_exists-Guard).
     */
    public function partnerEntity()
    {
        if (! class_exists(\Platform\Organization\Services\EntityDimensionBridge::class)) {
            return null;
        }

        return \Platform\Organization\Services\EntityDimensionBridge::linksForLinkables(
            ['sales_board'],
            [$this->id]
        )->first()?->entity;
    }

    /**
     * Setzt/ersetzt die Partner-Entity dieses Boards (null entfernt die Verknüpfung).
     */
    public function setPartnerEntity(?int $entityId): void
    {
        if (! class_exists(\Platform\Organization\Services\EntityDimensionBridge::class)) {
            return;
        }

        $bridge = \Platform\Organization\Services\EntityDimensionBridge::class;

        if ($entityId) {
            $bridge::replaceLinks('sales_board', $this->id, $entityId, [
                'team_id' => $this->team_id,
                'created_by_user_id' => auth()->id(),
            ]);
            return;
        }

        // Entfernen: bestehende Entity-Links dieses Boards abhängen
        foreach ($bridge::linksForLinkables(['sales_board'], [$this->id]) as $link) {
            if ($link->entity_id) {
                $bridge::deleteLink($link->entity_id, 'sales_board', $this->id);
            }
        }
    }
}
