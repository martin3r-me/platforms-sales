<?php

namespace Platform\Sales\Livewire;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Platform\Sales\Models\SalesDeal;
use Platform\Sales\Models\SalesBoard;
use Platform\Sales\Models\SalesBoardSlot;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Dashboard extends Component
{
    private function getTeamId(): ?int
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }
        $baseTeam = $user->currentTeamRelation;
        return $baseTeam ? $baseTeam->getRootTeam()->id : null;
    }

    private function baseQuery()
    {
        return SalesDeal::forTeam($this->getTeamId());
    }

    private function calculateTrend($current, $previous): array
    {
        if ($previous == 0 && $current == 0) {
            return ['trend' => null, 'trendValue' => null];
        }
        if ($previous == 0) {
            return ['trend' => 'up', 'trendValue' => '+100%'];
        }

        $diff = $current - $previous;
        $percent = round(($diff / $previous) * 100);

        if ($percent == 0) {
            return ['trend' => null, 'trendValue' => '0%'];
        }

        return [
            'trend' => $percent > 0 ? 'up' : 'down',
            'trendValue' => ($percent > 0 ? '+' : '') . $percent . '%',
        ];
    }

    // === TIER 1: Hero Tiles ===

    #[Computed]
    public function pipelineValue()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) return 0;
        return (float) $this->baseQuery()->open()->sum('deal_value');
    }

    #[Computed]
    public function pipelineValueLastMonth()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) return 0;
        $start = now()->subMonth()->startOfMonth();
        $end = now()->subMonth()->endOfMonth();
        // Open deals that existed last month: created before end of last month, not won/lost before end of last month
        return (float) $this->baseQuery()
            ->where('created_at', '<=', $end)
            ->where(function ($q) use ($end) {
                $q->where('is_done', false)->whereNull('lost_at');
                $q->orWhere('done_at', '>', $end);
                $q->orWhere('lost_at', '>', $end);
            })
            ->sum('deal_value');
    }

    #[Computed]
    public function pipelineValueTrend()
    {
        return $this->calculateTrend($this->pipelineValue, $this->pipelineValueLastMonth);
    }

    #[Computed]
    public function wonRevenueThisMonth()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) return 0;
        return (float) $this->baseQuery()
            ->won()
            ->where('done_at', '>=', now()->startOfMonth())
            ->sum('deal_value');
    }

    #[Computed]
    public function wonRevenueLastMonth()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) return 0;
        return (float) $this->baseQuery()
            ->won()
            ->whereBetween('done_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->sum('deal_value');
    }

    #[Computed]
    public function wonRevenueTrend()
    {
        return $this->calculateTrend($this->wonRevenueThisMonth, $this->wonRevenueLastMonth);
    }

    #[Computed]
    public function winRate()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) return 0;
        $start = now()->startOfMonth();
        $wonCount = $this->baseQuery()->won()->where('done_at', '>=', $start)->count();
        $lostCount = $this->baseQuery()->lost()->where('lost_at', '>=', $start)->count();
        $total = $wonCount + $lostCount;

        return $total > 0 ? round(($wonCount / $total) * 100) : 0;
    }

    #[Computed]
    public function winRateLastMonth()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) return 0;
        $start = now()->subMonth()->startOfMonth();
        $end = now()->subMonth()->endOfMonth();
        $wonCount = $this->baseQuery()->won()->whereBetween('done_at', [$start, $end])->count();
        $lostCount = $this->baseQuery()->lost()->whereBetween('lost_at', [$start, $end])->count();
        $total = $wonCount + $lostCount;

        return $total > 0 ? round(($wonCount / $total) * 100) : 0;
    }

    #[Computed]
    public function winRateTrend()
    {
        return $this->calculateTrend($this->winRate, $this->winRateLastMonth);
    }

    #[Computed]
    public function openDealsCount()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) return 0;
        return $this->baseQuery()->open()->count();
    }

    #[Computed]
    public function openDealsCountLastMonth()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) return 0;
        $end = now()->subMonth()->endOfMonth();
        return $this->baseQuery()
            ->where('created_at', '<=', $end)
            ->where(function ($q) use ($end) {
                $q->where('is_done', false)->whereNull('lost_at');
                $q->orWhere('done_at', '>', $end);
                $q->orWhere('lost_at', '>', $end);
            })
            ->count();
    }

    #[Computed]
    public function openDealsCountTrend()
    {
        return $this->calculateTrend($this->openDealsCount, $this->openDealsCountLastMonth);
    }

    #[Computed]
    public function averageDealSize()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) return 0;
        return (float) $this->baseQuery()
            ->won()
            ->where('done_at', '>=', now()->startOfMonth())
            ->avg('deal_value') ?? 0;
    }

    #[Computed]
    public function averageDealSizeLastMonth()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) return 0;
        return (float) $this->baseQuery()
            ->won()
            ->whereBetween('done_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->avg('deal_value') ?? 0;
    }

    #[Computed]
    public function averageDealSizeTrend()
    {
        return $this->calculateTrend($this->averageDealSize, $this->averageDealSizeLastMonth);
    }

    // === TIER 2: Pipeline Breakdown ===

    #[Computed]
    public function dealsByStage()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) return collect();

        return SalesBoardSlot::query()
            ->join('sales_boards', 'sales_board_slots.sales_board_id', '=', 'sales_boards.id')
            ->where('sales_boards.team_id', $teamId)
            ->leftJoin('sales_deals', function ($join) {
                $join->on('sales_deals.sales_board_slot_id', '=', 'sales_board_slots.id')
                    ->where('sales_deals.is_done', false)
                    ->whereNull('sales_deals.lost_at')
                    ->whereNull('sales_deals.deleted_at');
            })
            ->select(
                'sales_board_slots.id',
                'sales_board_slots.name',
                'sales_boards.name as board_name',
                DB::raw('COUNT(sales_deals.id) as deal_count'),
                DB::raw('COALESCE(SUM(sales_deals.deal_value), 0) as total_value')
            )
            ->groupBy('sales_board_slots.id', 'sales_board_slots.name', 'sales_boards.name')
            ->orderBy('sales_boards.name')
            ->orderBy('sales_board_slots.order')
            ->get();
    }

    #[Computed]
    public function overdueDeals()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) return collect();

        return $this->baseQuery()
            ->open()
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->orderBy('due_date')
            ->take(10)
            ->get();
    }

    // === TIER 3: Activity ===

    #[Computed]
    public function recentWonDeals()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) return collect();

        return $this->baseQuery()
            ->won()
            ->orderByDesc('done_at')
            ->take(5)
            ->get();
    }

    #[Computed]
    public function hotDeals()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) return collect();

        return $this->baseQuery()
            ->open()
            ->where(function ($q) {
                $q->where('is_hot', true)
                  ->orWhere('probability_percent', '>=', 80);
            })
            ->orderByDesc('deal_value')
            ->take(5)
            ->get();
    }

    #[Computed]
    public function upcomingCloseDeals()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) return collect();

        return $this->baseQuery()
            ->open()
            ->whereNotNull('close_date')
            ->where('close_date', '>=', now())
            ->where('close_date', '<=', now()->addDays(30))
            ->orderBy('close_date')
            ->take(5)
            ->get();
    }

    public function render()
    {
        return view('sales::livewire.dashboard')
            ->layout('platform::layouts.app');
    }
}
