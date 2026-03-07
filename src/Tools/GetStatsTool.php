<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Sales\Tools\Concerns\ResolvesSalesTeam;
use Platform\Sales\Models\SalesDeal;
use Platform\Sales\Models\SalesBoard;
use Carbon\Carbon;

class GetStatsTool implements ToolContract, ToolMetadataContract
{
    use ResolvesSalesTeam;

    public function getName(): string
    {
        return 'sales.stats.GET';
    }

    public function getDescription(): string
    {
        return 'GET /sales/stats - Zeigt Pipeline-Statistiken und KPIs: Gesamtwert offener/gewonnener Deals, Erwartungswert, Deal-Anzahl, Performance-Score, Board-Übersicht. Kann auf ein Board oder einen Zeitraum gefiltert werden.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Standard: aus dem Kontext.',
                ],
                'board_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Nur Statistiken für ein bestimmtes Board.',
                ],
                'user_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Nur Statistiken für einen bestimmten verantwortlichen User.',
                ],
                'period' => [
                    'type' => 'string',
                    'description' => 'Optional: Zeitraum für Performance-Berechnung. Erlaubte Werte: week, month, quarter, year. Standard: month.',
                ],
            ],
            'required' => [],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = $resolved['team_id'];

            // Basis-Query
            $baseQuery = SalesDeal::query()->where('team_id', $teamId);

            if (!empty($arguments['board_id'])) {
                $baseQuery->where('sales_board_id', (int) $arguments['board_id']);
            }
            if (!empty($arguments['user_id'])) {
                $baseQuery->where('user_in_charge_id', (int) $arguments['user_id']);
            }

            // Offene Deals
            $openDeals = (clone $baseQuery)->where('is_done', false)->get();
            $wonDeals = (clone $baseQuery)->where('is_done', true)->get();

            $openCount = $openDeals->count();
            $wonCount = $wonDeals->count();
            $openValue = (float) $openDeals->sum('deal_value');
            $wonValue = (float) $wonDeals->sum('deal_value');
            $openExpectedValue = (float) $openDeals->sum(function ($deal) {
                $value = (float) ($deal->deal_value ?? 0);
                $prob = (float) ($deal->probability_percent ?? 0);
                return $value * $prob / 100;
            });

            // Überfällige Deals
            $overdueCount = $openDeals->filter(fn ($d) => $d->due_date && $d->due_date->isPast())->count();
            $hotCount = $openDeals->filter(fn ($d) => $d->is_hot)->count();

            // Performance im Zeitraum
            $period = $arguments['period'] ?? 'month';
            $periodStart = match ($period) {
                'week' => now()->startOfWeek(),
                'quarter' => now()->startOfQuarter(),
                'year' => now()->startOfYear(),
                default => now()->startOfMonth(),
            };

            $periodQuery = SalesDeal::query()->where('team_id', $teamId);
            if (!empty($arguments['board_id'])) {
                $periodQuery->where('sales_board_id', (int) $arguments['board_id']);
            }
            if (!empty($arguments['user_id'])) {
                $periodQuery->where('user_in_charge_id', (int) $arguments['user_id']);
            }

            $createdInPeriod = (clone $periodQuery)->where('created_at', '>=', $periodStart)->count();
            $wonInPeriod = (clone $periodQuery)->where('is_done', true)->where('done_at', '>=', $periodStart)->count();
            $wonValueInPeriod = (float) (clone $periodQuery)->where('is_done', true)->where('done_at', '>=', $periodStart)->sum('deal_value');

            $winRate = $createdInPeriod > 0 ? round($wonInPeriod / $createdInPeriod * 100, 1) : null;

            // Board-Übersicht
            $boards = SalesBoard::where('team_id', $teamId)->orderBy('name')->get()->map(function ($board) {
                $openCount = $board->deals()->where('is_done', false)->count();
                $openValue = (float) $board->deals()->where('is_done', false)->sum('deal_value');
                return [
                    'id' => $board->id,
                    'name' => $board->name,
                    'open_deals' => $openCount,
                    'open_value' => $openValue,
                ];
            });

            return ToolResult::success([
                'pipeline' => [
                    'open_deals' => $openCount,
                    'open_value' => $openValue,
                    'open_expected_value' => $openExpectedValue,
                    'won_deals' => $wonCount,
                    'won_value' => $wonValue,
                    'overdue_deals' => $overdueCount,
                    'hot_deals' => $hotCount,
                ],
                'performance' => [
                    'period' => $period,
                    'period_start' => $periodStart->toDateString(),
                    'deals_created' => $createdInPeriod,
                    'deals_won' => $wonInPeriod,
                    'won_value' => $wonValueInPeriod,
                    'win_rate_percent' => $winRate,
                ],
                'boards' => $boards->toArray(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['sales', 'stats', 'pipeline', 'kpi', 'performance', 'dashboard'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'safe',
            'idempotent' => true,
            'confirmation_required' => false,
        ];
    }
}
