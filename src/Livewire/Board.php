<?php

namespace Platform\Sales\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Platform\Sales\Models\SalesBoard;
use Platform\Sales\Models\SalesDeal;
use Platform\Sales\Models\SalesBoardSlot;
use Illuminate\Support\Facades\Auth;

class Board extends Component
{
    public SalesBoard $salesBoard;
    public bool $showWonColumn = false;
    public bool $showLostColumn = false;

    public function mount(SalesBoard $salesBoard)
    {
        $this->salesBoard = $salesBoard;
    }

    #[On('board-updated')]
    #[On('slot-updated')]
    #[On('slot-deleted')]
    #[On('deal-updated')]
    public function refreshBoard()
    {
        $this->salesBoard->refresh();
    }

    public function createDeal($slotId = null)
    {
        $deal = new SalesDeal();
        $deal->sales_board_id = $this->salesBoard->id;
        $deal->sales_board_slot_id = $slotId;
        $deal->user_id = Auth::id();
        $deal->team_id = $this->salesBoard->team_id;
        $deal->title = 'Neuer Deal';
        $deal->deal_value = null;
        $deal->probability_percent = null;
        $deal->deal_source = null;
        $deal->deal_type = null;
        $deal->is_done = false;
        $deal->order = 0;
        $deal->slot_order = 0;
        $deal->save();

        $this->dispatch('deal-created', dealId: $deal->id);
    }

    public function createBoardSlot()
    {
        $slot = new SalesBoardSlot();
        $slot->sales_board_id = $this->salesBoard->id;
        $slot->name = 'Neue Spalte';
        $slot->order = $this->salesBoard->slots()->count();
        $slot->save();

        $this->dispatch('slot-created', slotId: $slot->id);
    }

    public function updateDealOrder($groups)
    {
        foreach ($groups as $group) {
            $slotId = $group['value'];

            foreach ($group['items'] as $item) {
                $deal = SalesDeal::find($item['value']);

                if (!$deal) {
                    continue;
                }

                // Bestimme das neue Slot basierend auf der Gruppe
                $newSlotId = null;
                $isDone = false;

                if ($slotId === 'won') {
                    $deal->sales_board_slot_id = null;
                    $deal->slot_order = $item['order'];
                    $deal->order = $item['order'];
                    $deal->markAsWon();
                    continue;
                }

                if ($slotId === 'lost') {
                    $deal->sales_board_slot_id = null;
                    $deal->slot_order = $item['order'];
                    $deal->order = $item['order'];
                    $deal->markAsLost();
                    continue;
                }

                // Regular pipeline slot — reopen if coming from won/lost
                $slot = $this->salesBoard->slots()->find($slotId);
                if ($slot) {
                    $newSlotId = $slot->id;
                }

                $deal->sales_board_slot_id = $newSlotId;
                $deal->slot_order = $item['order'];
                $deal->order = $item['order'];
                $deal->is_done = false;
                $deal->done_at = null;
                $deal->lost_at = null;
                $deal->lost_reason = null;
                $deal->save();
            }
        }
    }

    /**
     * Aktualisiert Reihenfolge der Slots nach Drag&Drop.
     */
    public function updateDealGroupOrder($groups)
    {
        foreach ($groups as $slotGroup) {
            $slotDb = SalesBoardSlot::find($slotGroup['value']);
            if ($slotDb) {
                $slotDb->order = $slotGroup['order'];
                $slotDb->save();
            }
        }
    }

    public function toggleShowWonColumn()
    {
        $this->showWonColumn = !$this->showWonColumn;
    }

    public function toggleShowLostColumn()
    {
        $this->showLostColumn = !$this->showLostColumn;
    }

    public function render()
    {
        // === 1. PIPELINE-SPALTEN ===
        $slots = $this->salesBoard->slots()
            ->with(['deals' => function ($q) {
                $q->open()
                  ->orderBy('slot_order')
                  ->orderBy('order');
            }])
            ->orderBy('order')
            ->get()
            ->map(function ($slot) {
                return (object) [
                    'id' => $slot->id,
                    'label' => $slot->name,
                    'isWonGroup' => false,
                    'isLostGroup' => false,
                    'deals' => $slot->deals,
                ];
            });

        // === 2. GEWONNENE DEALS ===
        $wonDeals = $this->salesBoard->deals()
            ->won()
            ->orderByDesc('done_at')
            ->get();

        $wonGroup = (object) [
            'id' => 'won',
            'label' => 'GEWONNEN',
            'isWonGroup' => true,
            'isLostGroup' => false,
            'deals' => $wonDeals,
        ];

        // === 3. VERLORENE DEALS ===
        $lostDeals = $this->salesBoard->deals()
            ->lost()
            ->orderByDesc('lost_at')
            ->get();

        $lostGroup = (object) [
            'id' => 'lost',
            'label' => 'VERLOREN',
            'isWonGroup' => false,
            'isLostGroup' => true,
            'deals' => $lostDeals,
        ];

        // === GRUPPEN ZUSAMMENSTELLEN ===
        $groups = $slots->push($wonGroup)->push($lostGroup);

        return view('sales::livewire.board', [
            'groups' => $groups,
        ])->layout('platform::layouts.app');
    }
}
