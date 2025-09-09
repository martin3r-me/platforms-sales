<?php

namespace Platform\Sales\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Sales\Models\SalesDeal;
use Platform\Sales\Models\SalesBoard;
use Carbon\Carbon;

class Dashboard extends Component
{
    public $perspective = 'team';

    public function render()
    {
        $user = Auth::user();
        $team = $user->currentTeam;
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        // === BOARDS (nur Team-Boards) ===
        $boards = SalesBoard::where('team_id', $team->id)->orderBy('name')->get();
        $activeBoards = $boards->count();
        $totalBoards = $boards->count();

        if ($this->perspective === 'personal') {
            // === PERSÖNLICHE DEALS ===
            $myDeals = SalesDeal::query()
                ->where('user_in_charge_id', $user->id)
                ->where('team_id', $team->id)
                ->get();

            $openDeals = $myDeals->where('is_done', false)->count();
            $wonDeals = $myDeals->where('is_done', true)->count();
            $totalDeals = $myDeals->count();
            $highValueDeals = $myDeals->where('deal_value', '>', 10000)->count();
            $overdueDeals = $myDeals->where('is_done', false)
                ->filter(fn($deal) => $deal->due_date && $deal->due_date->isPast())
                ->count();

            // === PERSÖNLICHE MONATLICHE PERFORMANCE ===
            $monthlyCreatedDeals = SalesDeal::query()
                ->where('team_id', $team->id)
                ->where('user_in_charge_id', $user->id)
                ->whereDate('created_at', '>=', $startOfMonth)
                ->count();

            $monthlyWonDeals = SalesDeal::query()
                ->where('team_id', $team->id)
                ->where('user_in_charge_id', $user->id)
                ->whereDate('done_at', '>=', $startOfMonth)
                ->count();

            // === PERSÖNLICHE UMSATZ-STATISTIKEN ===
            $totalDealValue = $myDeals->where('is_done', false)->sum('deal_value') ?? 0;
            $expectedValue = $myDeals->where('is_done', false)->sum(function($deal) {
                return $deal->expected_value ?? 0;
            });
            $wonDealValue = $myDeals->where('is_done', true)->sum('deal_value') ?? 0;

            // === PERSÖNLICHE AMPEL-STATISTIKEN ===
            $redDeals = $myDeals->where('is_done', false)
                ->filter(fn($deal) => $deal->probability_percent <= 30)
                ->count();
            $yellowDeals = $myDeals->where('is_done', false)
                ->filter(fn($deal) => $deal->probability_percent > 30 && $deal->probability_percent <= 70)
                ->count();
            $greenDeals = $myDeals->where('is_done', false)
                ->filter(fn($deal) => $deal->probability_percent > 70)
                ->count();

        } else {
            // === TEAM-DEALS ===
            $teamDeals = SalesDeal::query()
                ->where('team_id', $team->id)
                ->get();

            $openDeals = $teamDeals->where('is_done', false)->count();
            $wonDeals = $teamDeals->where('is_done', true)->count();
            $totalDeals = $teamDeals->count();
            $highValueDeals = $teamDeals->where('deal_value', '>', 10000)->count();
            $overdueDeals = $teamDeals->where('is_done', false)
                ->filter(fn($deal) => $deal->due_date && $deal->due_date->isPast())
                ->count();

            // === TEAM MONATLICHE PERFORMANCE ===
            $monthlyCreatedDeals = SalesDeal::query()
                ->where('team_id', $team->id)
                ->whereDate('created_at', '>=', $startOfMonth)
                ->count();

            $monthlyWonDeals = SalesDeal::query()
                ->where('team_id', $team->id)
                ->whereDate('done_at', '>=', $startOfMonth)
                ->count();

            // === TEAM UMSATZ-STATISTIKEN ===
            $totalDealValue = $teamDeals->where('is_done', false)->sum('deal_value') ?? 0;
            $expectedValue = $teamDeals->where('is_done', false)->sum(function($deal) {
                return $deal->expected_value ?? 0;
            });
            $wonDealValue = $teamDeals->where('is_done', true)->sum('deal_value') ?? 0;

            // === TEAM AMPEL-STATISTIKEN ===
            $redDeals = $teamDeals->where('is_done', false)
                ->filter(fn($deal) => $deal->probability_percent <= 30)
                ->count();
            $yellowDeals = $teamDeals->where('is_done', false)
                ->filter(fn($deal) => $deal->probability_percent > 30 && $deal->probability_percent <= 70)
                ->count();
            $greenDeals = $teamDeals->where('is_done', false)
                ->filter(fn($deal) => $deal->probability_percent > 70)
                ->count();
        }

        // === BOARD-ÜBERSICHT ===
        $perspective = $this->perspective;
        $activeBoardsList = $boards->map(function ($board) use ($user, $perspective) {
            if ($perspective === 'personal') {
                $boardDeals = SalesDeal::where('sales_board_id', $board->id)
                    ->where('user_in_charge_id', $user->id)
                    ->get();
            } else {
                $boardDeals = SalesDeal::where('sales_board_id', $board->id)->get();
            }
            
            return [
                'id' => $board->id,
                'name' => $board->name,
                'open_deals' => $boardDeals->where('is_done', false)->count(),
                'total_deals' => $boardDeals->count(),
                'high_value' => $boardDeals->where('deal_value', '>', 10000)->count(),
                'total_value' => $boardDeals->where('is_done', false)->sum('deal_value') ?? 0,
            ];
        })
        ->sortByDesc('open_deals')
        ->take(5);

        return view('sales::livewire.dashboard', [
            'currentDate' => now()->format('d.m.Y'),
            'currentDay' => now()->format('l'),
            'perspective' => $this->perspective,
            'activeBoards' => $activeBoards,
            'totalBoards' => $totalBoards,
            'openDeals' => $openDeals,
            'wonDeals' => $wonDeals,
            'totalDeals' => $totalDeals,
            'highValueDeals' => $highValueDeals,
            'overdueDeals' => $overdueDeals,
            'monthlyCreatedDeals' => $monthlyCreatedDeals,
            'monthlyWonDeals' => $monthlyWonDeals,
            'totalDealValue' => $totalDealValue,
            'expectedValue' => $expectedValue,
            'wonDealValue' => $wonDealValue,
            'redDeals' => $redDeals,
            'yellowDeals' => $yellowDeals,
            'greenDeals' => $greenDeals,
            'activeBoardsList' => $activeBoardsList,
        ])->layout('platform::layouts.app');
    }
}