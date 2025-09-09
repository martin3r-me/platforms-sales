<?php

namespace Platform\Sales\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Sales\Models\SalesDeal;
use Platform\Sales\Models\SalesBoard;
use Livewire\Attributes\On;

class MyDeals extends Component
{
    #[On('updateDashboard')] 
    public function updateDashboard()
    {
        
    }

    #[On('dealUpdated')]
    public function dealsUpdated()
    {
        // Optional: neu rendern bei Event
    }

    public function render()
    {
        $user = Auth::user();
        $userId = $user->id;
        $startOfMonth = now()->startOfMonth();

        // === 1. INBOX (Deals ohne Board) ===
        $inboxDeals = SalesDeal::query()
            ->whereNull('sales_board_id')
            ->where('is_done', false)
            ->where('user_in_charge_id', $userId)
            ->orderBy('order')
            ->get();

        $inbox = (object) [
            'id' => null,
            'label' => 'INBOX',
            'isInbox' => true,
            'tasks' => $inboxDeals,
            'open_count' => $inboxDeals->count(),
            'total_value' => $inboxDeals->sum('deal_value') ?? 0,
            'expected_value' => $inboxDeals->sum(function($deal) {
                return $deal->expected_value ?? 0;
            }),
        ];

        // === 2. BOARD-DEALS ===
        $boardDeals = SalesDeal::query()
            ->whereNotNull('sales_board_id')
            ->where('is_done', false)
            ->where('user_in_charge_id', $userId)
            ->with(['salesBoard', 'salesBoardSlot'])
            ->orderBy('order')
            ->get()
            ->groupBy('sales_board_id');

        $boardGroups = $boardDeals->map(function ($deals, $boardId) {
            $board = $deals->first()->salesBoard;
            return (object) [
                'id' => $boardId,
                'label' => $board->name,
                'isInbox' => false,
                'isBoardGroup' => true,
                'tasks' => $deals,
                'open_count' => $deals->count(),
                'total_value' => $deals->sum('deal_value') ?? 0,
                'expected_value' => $deals->sum(function($deal) {
                    return $deal->expected_value ?? 0;
                }),
            ];
        });

        // === 3. GEWONNENE DEALS ===
        $wonDeals = SalesDeal::query()
            ->where('is_done', true)
            ->where('user_in_charge_id', $userId)
            ->orderByDesc('done_at')
            ->get();

        $wonGroup = (object) [
            'id' => 'won',
            'label' => 'Gewonnen',
            'isInbox' => false,
            'isWonGroup' => true,
            'tasks' => $wonDeals,
            'total_value' => $wonDeals->sum('deal_value') ?? 0,
        ];

        // === 4. KOMPLETTE GRUPPENLISTE ===
        $groups = collect([$inbox])->concat($boardGroups)->push($wonGroup);

        // === 5. PERFORMANCE-BERECHNUNG ===
        $createdValue = SalesDeal::query()
            ->withTrashed()
            ->whereDate('created_at', '>=', $startOfMonth)
            ->where('user_in_charge_id', $userId)
            ->get()
            ->sum('deal_value') ?? 0;

        $wonValue = SalesDeal::query()
            ->withTrashed()
            ->whereDate('done_at', '>=', $startOfMonth)
            ->where('user_in_charge_id', $userId)
            ->get()
            ->sum('deal_value') ?? 0;

        $monthlyPerformanceScore = $createdValue > 0 ? round($wonValue / $createdValue, 2) : null;

        return view('sales::livewire.my-deals', [
            'groups' => $groups,
            'monthlyPerformanceScore' => $monthlyPerformanceScore,
            'createdValue' => $createdValue,
            'wonValue' => $wonValue,
        ])->layout('platform::layouts.app');
    }

    public function createDeal($boardId = null)
    {
        $user = Auth::user();
        
        $lowestOrder = SalesDeal::where('user_in_charge_id', Auth::id())
            ->where('team_id', Auth::user()->currentTeam->id)
            ->min('order') ?? 0;

        $order = $lowestOrder - 1;

        $newDeal = SalesDeal::create([
            'user_id' => Auth::id(),
            'user_in_charge_id' => $user->id,
            'sales_board_id' => $boardId,
            'title' => 'Neuer Deal',
            'description' => null,
            'due_date' => null,
            'deal_value' => null,
            'probability_percent' => null,
            'deal_source' => null,
            'deal_type' => null,
            'team_id' => Auth::user()->currentTeam->id,
            'order' => $order,
        ]);
    }

    public function toggleDone($dealId)
    {
        $deal = SalesDeal::findOrFail($dealId);

        if ($deal->user_in_charge_id !== auth()->id()) {
            abort(403);
        }

        $deal->update([
            'is_done' => ! $deal->is_done,
            'done_at' => ! $deal->is_done ? now() : null,
        ]);
    }

    public function updateDealOrder($groups)
    {
        foreach ($groups as $group) {
            $boardId = ($group['value'] === 'null' || (int) $group['value'] === 0)
                ? null
                : (int) $group['value'];

            foreach ($group['items'] as $item) {
                $deal = SalesDeal::find($item['value']);

                if (! $deal) {
                    continue;
                }

                $deal->order = $item['order'];
                $deal->sales_board_id = $boardId;
                $deal->save();
            }
        }
    }
}