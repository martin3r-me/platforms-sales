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

class CreateDealTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations, ResolvesSalesTeam;

    public function getName(): string
    {
        return 'sales.deals.POST';
    }

    public function getDescription(): string
    {
        return 'POST /sales/deals - Erstellt einen neuen Deal. Kann einem Board/Slot zugewiesen werden. Unterstützt Dealwert, Wahrscheinlichkeit, Billing-Intervall und viele weitere Felder.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Standard: aus dem Kontext.',
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Required: Titel des Deals.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung.',
                ],
                'notes' => [
                    'type' => 'string',
                    'description' => 'Optional: Interne Notizen.',
                ],
                'board_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Board-ID. Nutze "sales.boards.GET" um Boards zu finden.',
                ],
                'slot_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Slot-ID (Kanban-Spalte). Nutze "sales.board_slots.GET" um Slots zu finden.',
                ],
                'user_in_charge_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: ID des verantwortlichen Users.',
                ],
                'deal_value' => [
                    'type' => 'number',
                    'description' => 'Optional: Dealwert in EUR.',
                ],
                'probability_percent' => [
                    'type' => 'integer',
                    'description' => 'Optional: Abschlusswahrscheinlichkeit (0-100).',
                ],
                'due_date' => [
                    'type' => 'string',
                    'description' => 'Optional: Fälligkeitsdatum (YYYY-MM-DD).',
                ],
                'close_date' => [
                    'type' => 'string',
                    'description' => 'Optional: Erwartetes Abschlussdatum (YYYY-MM-DD).',
                ],
                'billing_interval' => [
                    'type' => 'string',
                    'description' => 'Optional: Abrechnungsintervall. Erlaubte Werte: one_time, monthly, quarterly, yearly.',
                ],
                'billing_duration_months' => [
                    'type' => 'integer',
                    'description' => 'Optional: Laufzeit in Monaten (nur bei wiederkehrender Abrechnung).',
                ],
                'monthly_recurring_value' => [
                    'type' => 'number',
                    'description' => 'Optional: Monatlicher Wiederkehrender Wert in EUR.',
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
                    'description' => 'Optional: Prioritäts-ID.',
                ],
                'sales_deal_source_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Deal-Quellen-ID.',
                ],
                'sales_deal_type_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Deal-Typ-ID.',
                ],
                'is_hot' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Als heißen Deal markieren.',
                ],
                'is_starred' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Als Favorit markieren.',
                ],
            ],
            'required' => ['title'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = $resolved['team_id'];

            $title = trim($arguments['title'] ?? '');
            if ($title === '') {
                return ToolResult::error('VALIDATION_ERROR', 'title ist erforderlich.');
            }

            // Billing-Intervall validieren
            if (isset($arguments['billing_interval'])) {
                $allowed = ['one_time', 'monthly', 'quarterly', 'yearly'];
                if (!in_array($arguments['billing_interval'], $allowed)) {
                    return ToolResult::error('VALIDATION_ERROR', 'billing_interval muss einer der folgenden Werte sein: ' . implode(', ', $allowed));
                }
            }

            // Probability validieren
            if (isset($arguments['probability_percent'])) {
                $prob = (int) $arguments['probability_percent'];
                if ($prob < 0 || $prob > 100) {
                    return ToolResult::error('VALIDATION_ERROR', 'probability_percent muss zwischen 0 und 100 liegen.');
                }
            }

            // Board-Zugehörigkeit prüfen
            if (!empty($arguments['board_id'])) {
                $board = SalesBoard::where('id', (int) $arguments['board_id'])->where('team_id', $teamId)->first();
                if (!$board) {
                    return ToolResult::error('NOT_FOUND', 'Board nicht gefunden oder gehört nicht zum Team. Nutze "sales.boards.GET" um Boards zu finden.');
                }
            }

            // Slot-Zugehörigkeit prüfen
            if (!empty($arguments['slot_id'])) {
                $slot = SalesBoardSlot::find((int) $arguments['slot_id']);
                if (!$slot || ($board ?? null) && $slot->sales_board_id !== $board->id) {
                    return ToolResult::error('NOT_FOUND', 'Slot nicht gefunden oder gehört nicht zum Board. Nutze "sales.board_slots.GET" um Slots zu finden.');
                }
            }

            $deal = SalesDeal::create([
                'title' => $title,
                'description' => $arguments['description'] ?? null,
                'notes' => $arguments['notes'] ?? null,
                'team_id' => $teamId,
                'user_id' => $context->user->id,
                'user_in_charge_id' => $arguments['user_in_charge_id'] ?? $context->user->id,
                'sales_board_id' => $arguments['board_id'] ?? null,
                'sales_board_slot_id' => $arguments['slot_id'] ?? null,
                'deal_value' => $arguments['deal_value'] ?? null,
                'probability_percent' => $arguments['probability_percent'] ?? null,
                'due_date' => $arguments['due_date'] ?? null,
                'close_date' => $arguments['close_date'] ?? null,
                'billing_interval' => $arguments['billing_interval'] ?? null,
                'billing_duration_months' => $arguments['billing_duration_months'] ?? null,
                'monthly_recurring_value' => $arguments['monthly_recurring_value'] ?? null,
                'competitor' => $arguments['competitor'] ?? null,
                'next_step' => $arguments['next_step'] ?? null,
                'next_step_date' => $arguments['next_step_date'] ?? null,
                'sales_priority_id' => $arguments['sales_priority_id'] ?? null,
                'sales_deal_source_id' => $arguments['sales_deal_source_id'] ?? null,
                'sales_deal_type_id' => $arguments['sales_deal_type_id'] ?? null,
                'is_hot' => $arguments['is_hot'] ?? false,
                'is_starred' => $arguments['is_starred'] ?? false,
                'is_done' => false,
                'order' => 0,
                'slot_order' => 0,
            ]);

            return ToolResult::success([
                'id' => $deal->id,
                'title' => $deal->title,
                'deal_value' => (float) ($deal->deal_value ?? 0),
                'probability_percent' => $deal->probability_percent,
                'board_id' => $deal->sales_board_id,
                'slot_id' => $deal->sales_board_slot_id,
                'message' => "Deal '{$deal->title}' erfolgreich erstellt.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['sales', 'deals', 'create', 'pipeline'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'write',
            'idempotent' => false,
            'confirmation_required' => false,
        ];
    }
}
