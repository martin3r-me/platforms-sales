<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Sales\Tools\Concerns\ResolvesSalesTeam;
use Platform\Sales\Models\SalesDeal;
use Platform\Sales\Models\SalesDealBillable;

class CreateDealBillableTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations, ResolvesSalesTeam;

    public function getName(): string
    {
        return 'sales.deal_billables.POST';
    }

    public function getDescription(): string
    {
        return 'POST /sales/deal_billables - Erstellt eine neue Abrechnungsposition (Billable) für einen Deal. Unterstützt einmalige und wiederkehrende Positionen. Der Gesamtwert wird automatisch berechnet. Nutze "sales.deals.GET" um die Deal-ID zu finden.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'deal_id' => [
                    'type' => 'integer',
                    'description' => 'Required: ID des Deals.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Required: Name der Position (z.B. "Lizenzgebühr", "Setup-Fee", "Wartungsvertrag").',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung.',
                ],
                'amount' => [
                    'type' => 'number',
                    'description' => 'Required: Betrag in EUR (pro Intervall bei wiederkehrend, gesamt bei einmalig).',
                ],
                'billing_type' => [
                    'type' => 'string',
                    'description' => 'Required: Abrechnungstyp. Erlaubte Werte: one_time, recurring.',
                ],
                'billing_interval' => [
                    'type' => 'string',
                    'description' => 'Optional: Intervall bei recurring. Erlaubte Werte: monthly, quarterly, yearly. Nur bei billing_type=recurring.',
                ],
                'duration_months' => [
                    'type' => 'integer',
                    'description' => 'Optional: Laufzeit in Monaten. Nur bei billing_type=recurring.',
                ],
                'probability_percent' => [
                    'type' => 'integer',
                    'description' => 'Optional: Abschlusswahrscheinlichkeit dieser Position (0-100). Standard: 100.',
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
                    'description' => 'Optional: Position aktiv? Standard: true.',
                ],
            ],
            'required' => ['deal_id', 'name', 'amount', 'billing_type'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $dealId = $arguments['deal_id'] ?? null;
            if (!$dealId) {
                return ToolResult::error('VALIDATION_ERROR', 'deal_id ist erforderlich.');
            }

            $deal = SalesDeal::find((int) $dealId);
            if (!$deal) {
                return ToolResult::error('NOT_FOUND', 'Deal nicht gefunden.');
            }

            $resolved = $this->resolveTeam(['team_id' => $deal->team_id], $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }

            $name = trim($arguments['name'] ?? '');
            if ($name === '') {
                return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');
            }

            $amount = $arguments['amount'] ?? null;
            if ($amount === null || $amount <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'amount muss größer als 0 sein.');
            }

            $billingType = $arguments['billing_type'] ?? null;
            if (!in_array($billingType, ['one_time', 'recurring'])) {
                return ToolResult::error('VALIDATION_ERROR', 'billing_type muss "one_time" oder "recurring" sein.');
            }

            // Recurring-Felder validieren
            if ($billingType === 'recurring') {
                if (!empty($arguments['billing_interval'])) {
                    $allowed = ['monthly', 'quarterly', 'yearly'];
                    if (!in_array($arguments['billing_interval'], $allowed)) {
                        return ToolResult::error('VALIDATION_ERROR', 'billing_interval muss einer der folgenden Werte sein: ' . implode(', ', $allowed));
                    }
                }
            }

            // Probability validieren
            if (isset($arguments['probability_percent'])) {
                $prob = (int) $arguments['probability_percent'];
                if ($prob < 0 || $prob > 100) {
                    return ToolResult::error('VALIDATION_ERROR', 'probability_percent muss zwischen 0 und 100 liegen.');
                }
            }

            $billable = SalesDealBillable::create([
                'sales_deal_id' => $deal->id,
                'name' => $name,
                'description' => $arguments['description'] ?? null,
                'amount' => (float) $amount,
                'billing_type' => $billingType,
                'billing_interval' => $arguments['billing_interval'] ?? null,
                'duration_months' => $arguments['duration_months'] ?? null,
                'probability_percent' => $arguments['probability_percent'] ?? 100,
                'start_date' => $arguments['start_date'] ?? null,
                'end_date' => $arguments['end_date'] ?? null,
                'is_active' => $arguments['is_active'] ?? true,
                'order' => $deal->billables()->count(),
            ]);

            // Deal-Wert wird automatisch durch SalesDealBillable::saved aktualisiert
            $deal->refresh();

            return ToolResult::success([
                'id' => $billable->id,
                'name' => $billable->name,
                'amount' => (float) $billable->amount,
                'billing_type' => $billable->billing_type,
                'total_value' => (float) $billable->total_value,
                'expected_value' => (float) $billable->expected_value,
                'billing_description' => $billable->billing_description,
                'deal_total_value' => (float) $deal->deal_value,
                'message' => "Position '{$billable->name}' erstellt. Deal-Gesamtwert: " . number_format((float) $deal->deal_value, 2, ',', '.') . ' €.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['sales', 'deals', 'billables', 'create', 'billing', 'revenue'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'write',
            'idempotent' => false,
            'confirmation_required' => false,
        ];
    }
}
