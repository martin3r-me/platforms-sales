<?php

namespace Platform\Sales\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Sales\Models\SalesDeal;
use Platform\Sales\Models\SalesBoard;
use Carbon\Carbon;

class Dashboard extends Component
{

    public function render()
    {
        $user = Auth::user();
        $userId = $user->id;

        // === PERSÖNLICHE DEALS ===
        $myDeals = SalesDeal::query()
            ->where('user_in_charge_id', $userId)
            ->get();

        $openDeals = $myDeals->where('is_done', false);
        $wonDeals = $myDeals->where('is_done', true);

        // === STATISTIKEN ===
        $openDealsCount = $openDeals->count();
        $wonDealsCount = $wonDeals->count();
        $totalValue = $openDeals->sum('deal_value') ?? 0;
        $expectedValue = $openDeals->sum(function($deal) {
            return $deal->expected_value ?? 0;
        });

        // === NEUESTE DEALS ===
        $recentDeals = $myDeals
            ->sortByDesc('created_at')
            ->take(5);

        return view('sales::livewire.dashboard', [
            'openDealsCount' => $openDealsCount,
            'wonDealsCount' => $wonDealsCount,
            'totalValue' => $totalValue,
            'expectedValue' => $expectedValue,
            'recentDeals' => $recentDeals,
        ])->layout('platform::layouts.app');
    }
}