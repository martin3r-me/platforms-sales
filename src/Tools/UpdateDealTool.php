<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Sales\Tools\Concerns\ResolvesSalesTeam;
use Platform\Sales\Models\SalesDeal;
use Platform\Sales\Models\SalesBoard;
use Platform\Sales\Models\SalesBoardSlot;

class UpdateDealTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations, ResolvesSalesTeam;

    public function getName(): string
    {
        return 'sales.deals.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /sales/deals/{id} - Aktualisiert einen bestehenden Deal. Kann alle Felder ändern: Titel, Wert, Wahrscheinlichkeit, Board-Zuordnung, Status, etc. Nutze "sales.deals.GET" um die Deal-ID zu finden.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'deal_id' => [
                    'type' => 'integer',
                    'description' => 'Required: ID des Deals.',
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Titel.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue Beschreibung.',
                ],
                'notes' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue Notizen.',
                ],
                'board_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Board-ID. Nutze 0 um den Deal vom Board zu trennen.',
                ],
                'slot_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Slot-ID. Nutze 0 um den Deal aus dem Slot zu entfernen.',
                ],
                'user_in_charge_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Neuer verantwortlicher User. Nutze 0 um die Zuweisung zu entfernen.',
                ],
                'deal_value' => [
                    'type' => 'number',
                    'description' => 'Optional: Neuer Dealwert in EUR.',
                ],
                'probability_percent' => [
                    'type' => 'integer',
                    'description' => 'Optional: Neue Abschlusswahrscheinlichkeit (0-100).',
                ],
                'due_date' => [
                    'type' => 'string',
                    'description' => 'Optional: Neues Fälligkeitsdatum (YYYY-MM-DD). Leer für Entfernung.',
                ],
                'close_date' => [
                    'type' => 'string',
                    'description' => 'Optional: Neues Abschlussdatum (YYYY-MM-DD). Leer für Entfernung.',
                ],
                'billing_interval' => [
                    'type' => 'string',
                    'description' => 'Optional: Abrechnungsintervall (one_time, monthly, quarterly, yearly).',
                ],
                'billing_duration_months' => [
                    'type' => 'integer',
                    'description' => 'Optional: Laufzeit in Monaten.',
                ],
                'monthly_recurring_value' => [
                    'type' => 'number',
                    'description' => 'Optional: Monatlicher Wiederkehrender Wert in EUR.',
                ],
                'is_done' => [
                    'type' => 'boolean',
                    'description' => 'Optional: true = Deal als gewonnen markieren, false = Deal wieder öffnen.',
                ],
                'mark_as_lost' => [
                    'type' => 'boolean',
                    'description' => 'Optional: true = Deal als verloren markieren. Grund via lost_reason_code/lost_reason.',
                ],
                'lost_reason_code' => [
                    'type' => 'string',
                    'description' => 'Optional: strukturierter Verlustgrund-Code aus dem Lookup. Gültige Codes via "sales.lookups.GET" (type=lost_reasons). Unbekannte/inaktive Codes werden ignoriert.',
                ],
                'lost_reason' => [
                    'type' => 'string',
                    'description' => 'Optional: Freitext-Detail zum Verlustgrund.',
                ],
                'on_hold' => [
                    'type' => 'boolean',
                    'description' => 'Optional: true = Deal pausieren (on hold), false = Pause aufheben.',
                ],
                'is_hot' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Als heißen Deal markieren.',
                ],
                'is_starred' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Als Favorit markieren.',
                ],
                'competitor' => [
                    'type' => 'string',
                    'description' => 'Optional: Wettbewerber.',
                ],
                'next_step' => [
                    'type' => 'string',
                    'description' => 'Optional: Nächster Schritt.',
                ],
                'next_step_date' => [
                    'type' => 'string',
                    'description' => 'Optional: Datum des nächsten Schritts (YYYY-MM-DD).',
                ],
                'sales_priority_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Prioritäts-ID. Nutze 0 um die Priorität zu entfernen.',
                ],
                'sales_deal_source_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Deal-Quellen-ID. Nutze 0 um die Quelle zu entfernen.',
                ],
                'sales_deal_type_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Deal-Typ-ID. Nutze 0 um den Typ zu entfernen.',
                ],
                'entity_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Org-Entity des Abnehmers setzen. 0/null entfernt sie. Nutze "organization.entities.GET".',
                ],
            ],
            'required' => ['deal_id'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $found = $this->validateAndFindModel($arguments, $context, 'deal_id', SalesDeal::class, 'NOT_FOUND', 'Deal nicht gefunden.');
            if ($found['error']) {
                return $found['error'];
            }
            /** @var SalesDeal $deal */
            $deal = $found['model'];

            $resolved = $this->resolveTeam(['team_id' => $deal->team_id], $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }

            $updated = [];

            // Einfache String-/Numeric-Felder
            $directFields = [
                'title', 'description', 'notes', 'deal_value',
                'competitor', 'next_step', 'billing_interval',
                'billing_duration_months', 'monthly_recurring_value',
            ];
            foreach ($directFields as $field) {
                if (array_key_exists($field, $arguments)) {
                    $deal->$field = $arguments[$field];
                    $updated[] = $field;
                }
            }

            // Title darf nicht leer sein
            if (isset($arguments['title']) && trim($arguments['title']) === '') {
                return ToolResult::error('VALIDATION_ERROR', 'Titel darf nicht leer sein.');
            }

            // Billing-Intervall validieren
            if (isset($arguments['billing_interval'])) {
                $allowed = ['one_time', 'monthly', 'quarterly', 'yearly'];
                if (!in_array($arguments['billing_interval'], $allowed)) {
                    return ToolResult::error('VALIDATION_ERROR', 'billing_interval muss einer der folgenden Werte sein: ' . implode(', ', $allowed));
                }
            }

            // Probability
            if (array_key_exists('probability_percent', $arguments)) {
                $prob = $arguments['probability_percent'];
                if ($prob !== null && ($prob < 0 || $prob > 100)) {
                    return ToolResult::error('VALIDATION_ERROR', 'probability_percent muss zwischen 0 und 100 liegen.');
                }
                $deal->probability_percent = $prob;
                $updated[] = 'probability_percent';
            }

            // Datums-Felder
            foreach (['due_date', 'close_date', 'next_step_date'] as $dateField) {
                if (array_key_exists($dateField, $arguments)) {
                    $deal->$dateField = empty($arguments[$dateField]) ? null : $arguments[$dateField];
                    $updated[] = $dateField;
                }
            }

            // Boolean-Felder
            foreach (['is_hot', 'is_starred'] as $boolField) {
                if (array_key_exists($boolField, $arguments)) {
                    $deal->$boolField = (bool) $arguments[$boolField];
                    $updated[] = $boolField;
                }
            }

            // Status: gewonnen / wieder öffnen
            if (array_key_exists('is_done', $arguments)) {
                $deal->is_done = (bool) $arguments['is_done'];
                $deal->done_at = $deal->is_done ? now() : null;
                if ($deal->is_done) {
                    $deal->lost_at = null;
                    $deal->lost_reason = null;
                    $deal->lost_reason_code = null;
                    $deal->on_hold_at = null;
                }
                $updated[] = 'is_done';
            }

            // Status: verloren
            if (!empty($arguments['mark_as_lost'])) {
                $deal->lost_at = now();
                $deal->is_done = false;
                $deal->done_at = null;
                $deal->on_hold_at = null;
                $updated[] = 'mark_as_lost';
            }

            if (array_key_exists('lost_reason', $arguments)) {
                $deal->lost_reason = $arguments['lost_reason'] ?: null;
                $updated[] = 'lost_reason';
            }

            if (array_key_exists('lost_reason_code', $arguments)) {
                $code = $arguments['lost_reason_code'];
                $deal->lost_reason_code = SalesDeal::isValidLostReasonCode($code) ? $code : null;
                $updated[] = 'lost_reason_code';
            }

            // Status: On hold / Pause aufheben
            if (array_key_exists('on_hold', $arguments)) {
                if ((bool) $arguments['on_hold']) {
                    $deal->on_hold_at = $deal->on_hold_at ?? now();
                    $deal->is_done = false;
                    $deal->done_at = null;
                    $deal->lost_at = null;
                    $deal->lost_reason = null;
                    $deal->lost_reason_code = null;
                } else {
                    $deal->on_hold_at = null;
                }
                $updated[] = 'on_hold';
            }

            // Nullable FK-Felder (0 = null)
            $nullableFks = [
                'user_in_charge_id' => 'user_in_charge_id',
                'sales_priority_id' => 'sales_priority_id',
                'sales_deal_source_id' => 'sales_deal_source_id',
                'sales_deal_type_id' => 'sales_deal_type_id',
            ];
            foreach ($nullableFks as $argKey => $dbField) {
                if (array_key_exists($argKey, $arguments)) {
                    $val = $arguments[$argKey];
                    $deal->$dbField = ($val === 0 || $val === '0' || $val === null) ? null : (int) $val;
                    $updated[] = $dbField;
                }
            }

            // Board-Zuordnung
            if (array_key_exists('board_id', $arguments)) {
                $boardId = $arguments['board_id'];
                if ($boardId === 0 || $boardId === '0' || $boardId === null) {
                    $deal->sales_board_id = null;
                    $deal->sales_board_slot_id = null;
                } else {
                    $board = SalesBoard::where('id', (int) $boardId)->where('team_id', $deal->team_id)->first();
                    if (!$board) {
                        return ToolResult::error('NOT_FOUND', 'Board nicht gefunden.');
                    }
                    $deal->sales_board_id = $board->id;
                }
                $updated[] = 'board_id';
            }

            // Slot-Zuordnung
            if (array_key_exists('slot_id', $arguments)) {
                $slotId = $arguments['slot_id'];
                if ($slotId === 0 || $slotId === '0' || $slotId === null) {
                    $deal->sales_board_slot_id = null;
                } else {
                    $slot = SalesBoardSlot::find((int) $slotId);
                    if (!$slot || $slot->sales_board_id !== $deal->sales_board_id) {
                        return ToolResult::error('NOT_FOUND', 'Slot nicht gefunden oder gehört nicht zum Board.');
                    }
                    $deal->sales_board_slot_id = $slot->id;
                }
                $updated[] = 'slot_id';
            }

            // Abnehmer-Entity setzen/entfernen (DimensionLink dimension=entity)
            if (array_key_exists('entity_id', $arguments)) {
                $entityId = $arguments['entity_id'];
                if ($entityId === 0 || $entityId === '0') { $entityId = null; }

                if (! class_exists(\Platform\Organization\Services\EntityDimensionBridge::class)) {
                    return ToolResult::error('UNAVAILABLE', 'Organization-Modul ist nicht verfügbar; Entity-Verknüpfung nicht möglich.');
                }

                if ($entityId) {
                    $entity = class_exists(\Platform\Organization\Models\OrganizationEntity::class)
                        ? \Platform\Organization\Models\OrganizationEntity::where('id', (int) $entityId)
                            ->where('team_id', $deal->team_id)
                            ->first()
                        : null;
                    if (!$entity) {
                        return ToolResult::error('NOT_FOUND', 'Abnehmer-Entity nicht gefunden oder gehört nicht zum Team. Nutze "organization.entities.GET".');
                    }
                    \Platform\Organization\Services\EntityDimensionBridge::replaceLinks(
                        'sales_deal',
                        $deal->id,
                        $entity->id,
                        ['team_id' => $deal->team_id, 'created_by_user_id' => $context->user->id]
                    );
                } else {
                    foreach (\Platform\Organization\Services\EntityDimensionBridge::linksForLinkables(['sales_deal'], [$deal->id]) as $link) {
                        if ($link->entity_id) {
                            \Platform\Organization\Services\EntityDimensionBridge::deleteLink($link->entity_id, 'sales_deal', $deal->id);
                        }
                    }
                }
                $updated[] = 'entity';
            }

            if (empty($updated)) {
                return ToolResult::error('VALIDATION_ERROR', 'Keine Änderungen angegeben.');
            }

            $deal->save();

            return ToolResult::success([
                'id' => $deal->id,
                'title' => $deal->title,
                'deal_value' => (float) ($deal->deal_value ?? 0),
                'probability_percent' => $deal->probability_percent,
                'is_done' => $deal->is_done,
                'status' => $deal->status(),
                'status_label' => $deal->statusLabel(),
                'updated_fields' => $updated,
                'message' => "Deal '{$deal->title}' aktualisiert.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['sales', 'deals', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'write',
            'idempotent' => true,
            'confirmation_required' => false,
        ];
    }
}
