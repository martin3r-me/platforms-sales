<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Sales\Tools\Concerns\ResolvesSalesTeam;
use Platform\Sales\Models\SalesDealBillable;

class UpdateDealBillableTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations, ResolvesSalesTeam;

    public function getName(): string
    {
        return 'sales.deal_billables.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /sales/deal_billables/{id} - Aktualisiert eine bestehende Abrechnungsposition (Billable). Nur übergebene Felder werden geändert. Der Gesamtwert wird automatisch neu berechnet.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'billable_id' => [
                    'type' => 'integer',
                    'description' => 'Required: ID der Position.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Name der Position.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung.',
                ],
                'amount' => [
                    'type' => 'number',
                    'description' => 'Optional: Betrag in EUR.',
                ],
                'billing_type' => [
                    'type' => 'string',
                    'description' => 'Optional: Abrechnungstyp. Erlaubte Werte: one_time, recurring.',
                ],
                'billing_interval' => [
                    'type' => 'string',
                    'description' => 'Optional: Intervall bei recurring. Erlaubte Werte: monthly, quarterly, yearly.',
                ],
                'duration_months' => [
                    'type' => 'integer',
                    'description' => 'Optional: Laufzeit in Monaten. Null für unbegrenzt.',
                ],
                'probability_percent' => [
                    'type' => 'integer',
                    'description' => 'Optional: Abschlusswahrscheinlichkeit (0-100).',
                ],
                'start_date' => [
                    'type' => 'string',
                    'description' => 'Optional: Startdatum (YYYY-MM-DD).',
                ],
                'end_date' => [
                    'type' => 'string',
                    'description' => 'Optional: Enddatum (YYYY-MM-DD).',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Position aktiv?',
                ],
            ],
            'required' => ['billable_id'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $billableId = $arguments['billable_id'] ?? null;
            if (! $billableId) {
                return ToolResult::error('VALIDATION_ERROR', 'billable_id ist erforderlich.');
            }

            $billable = SalesDealBillable::find((int) $billableId);
            if (! $billable) {
                return ToolResult::error('NOT_FOUND', 'Position nicht gefunden.');
            }

            $deal = $billable->salesDeal;
            if (! $deal) {
                return ToolResult::error('NOT_FOUND', 'Zugehöriger Deal nicht gefunden.');
            }

            $resolved = $this->resolveTeam(['team_id' => $deal->team_id], $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }

            $updatable = [
                'name', 'description', 'amount', 'billing_type',
                'billing_interval', 'duration_months', 'probability_percent',
                'start_date', 'end_date', 'is_active',
            ];

            $changes = [];
            foreach ($updatable as $field) {
                if (array_key_exists($field, $arguments)) {
                    $changes[$field] = $arguments[$field];
                }
            }

            if (empty($changes)) {
                return ToolResult::error('VALIDATION_ERROR', 'Keine Änderungen angegeben.');
            }

            // Validate billing_type
            if (isset($changes['billing_type']) && ! in_array($changes['billing_type'], ['one_time', 'recurring'])) {
                return ToolResult::error('VALIDATION_ERROR', 'billing_type muss "one_time" oder "recurring" sein.');
            }

            // Validate billing_interval
            if (isset($changes['billing_interval']) && ! in_array($changes['billing_interval'], ['monthly', 'quarterly', 'yearly'])) {
                return ToolResult::error('VALIDATION_ERROR', 'billing_interval muss monthly, quarterly oder yearly sein.');
            }

            // Validate probability
            if (isset($changes['probability_percent'])) {
                $prob = (int) $changes['probability_percent'];
                if ($prob < 0 || $prob > 100) {
                    return ToolResult::error('VALIDATION_ERROR', 'probability_percent muss zwischen 0 und 100 liegen.');
                }
            }

            // Validate amount
            if (isset($changes['amount']) && $changes['amount'] <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'amount muss größer als 0 sein.');
            }

            $billable->update($changes);
            $deal->refresh();

            return ToolResult::success([
                'id' => $billable->id,
                'name' => $billable->name,
                'amount' => (float) $billable->amount,
                'billing_type' => $billable->billing_type,
                'billing_interval' => $billable->billing_interval,
                'duration_months' => $billable->duration_months,
                'start_date' => $billable->start_date?->toDateString(),
                'end_date' => $billable->end_date?->toDateString(),
                'total_value' => (float) $billable->total_value,
                'expected_value' => (float) $billable->expected_value,
                'billing_description' => $billable->billing_description,
                'updated_fields' => array_keys($changes),
                'deal_total_value' => (float) $deal->deal_value,
                'message' => "Position '{$billable->name}' aktualisiert. Deal-Gesamtwert: " . number_format((float) $deal->deal_value, 2, ',', '.') . ' €.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['sales', 'deals', 'billables', 'update', 'billing', 'revenue'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'write',
            'idempotent' => true,
            'confirmation_required' => false,
        ];
    }
}
