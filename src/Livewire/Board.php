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
                    $isDone = true;
                } else {
                    $slot = $this->salesBoard->slots()->find($slotId);
                    if ($slot) {
                        $newSlotId = $slot->id;
                    }
                }

                $deal->sales_board_slot_id = $newSlotId;
                $deal->slot_order = $item['order'];
                $deal->order = $item['order'];
                $deal->is_done = $isDone;
                $deal->done_at = $isDone ? now() : null;
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

    /**
     * Toggle für die Anzeige der Gewonnen-Spalte
     */
    public function toggleShowWonColumn()
    {
        $this->showWonColumn = !$this->showWonColumn;
    }

    public function render()
    {
        // === 1. PIPELINE-SPALTEN ===
        $slots = $this->salesBoard->slots()
            ->with(['deals' => function ($q) {
                $q->where('is_done', false)
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
                    'deals' => $slot->deals,
                ];
            });

        // === 2. GEWONNENE DEALS ===
        $wonDeals = $this->salesBoard->deals()
            ->where('is_done', true)
            ->orderByDesc('done_at')
            ->get();

        $wonGroup = (object) [
            'id' => 'won',
            'label' => 'GEWONNEN',
            'isWonGroup' => true,
            'deals' => $wonDeals,
        ];

        // === GRUPPEN ZUSAMMENSTELLEN ===
        $groups = $slots->push($wonGroup);

        return view('sales::livewire.board', [
            'groups' => $groups,
        ])->layout('platform::layouts.app');
    }
}
