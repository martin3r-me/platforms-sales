<?php

namespace Platform\Sales\Livewire;

use Livewire\Component;
use Platform\Sales\Models\SalesBoard;
use Platform\Sales\Models\SalesDeal;
use Platform\Sales\Models\SalesBoardSlot;
use Illuminate\Support\Facades\Auth;

class Board extends Component
{
    public SalesBoard $salesBoard;
    public $groups;

    public function mount(SalesBoard $salesBoard)
    {
        $this->salesBoard = $salesBoard;
        $this->loadGroups();
    }

    public function loadGroups()
    {
        // Lade alle Slots des Boards
        $slots = $this->salesBoard->slots()->orderBy('order')->get();
        
        // Erstelle Gruppen für jedes Slot
        $groups = $slots->map(function ($slot) {
            $slot->label = $slot->name;
            $deals = $slot->deals()
                ->orderBy('slot_order')
                ->orderBy('order')
                ->get();
            $slot->deals = $deals; // Direkt die Eloquent Collection verwenden
            return $slot;
        });

        // Füge eine "Gewonnen" Gruppe hinzu
        $wonGroup = new SalesBoardSlot();
        $wonGroup->id = 'won';
        $wonGroup->name = 'GEWONNEN';
        $wonGroup->label = 'GEWONNEN';
        $wonGroup->isWonGroup = true;
        $wonDeals = $this->salesBoard->deals()
            ->where('is_done', true)
            ->orderBy('done_at', 'desc')
            ->get();
        $wonGroup->deals = $wonDeals; // Direkt die Eloquent Collection verwenden
        
        // Stelle sicher, dass $this->groups eine Collection ist
        $this->groups = $groups->push($wonGroup);
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

        $this->loadGroups();
        $this->dispatch('deal-created', dealId: $deal->id);
    }

    public function createBoardSlot()
    {
        $slot = new SalesBoardSlot();
        $slot->sales_board_id = $this->salesBoard->id;
        $slot->name = 'Neue Spalte';
        $slot->order = $this->salesBoard->slots()->count();
        $slot->save();

        $this->loadGroups();
        $this->dispatch('slot-created', slotId: $slot->id);
    }

    public function updateDealOrder($groups)
    {
        foreach ($groups as $group) {
            $slotId = ($group['value'] === 'null' || (int) $group['value'] === 0)
                ? null
                : (int) $group['value'];

            foreach ($group['items'] as $item) {
                $deal = SalesDeal::find($item['value']);

                if (!$deal) {
                    continue;
                }

                // Bestimme das neue Slot basierend auf der Gruppe
                $newSlotId = null;
                if ($slotId !== 'won') {
                    $slot = $this->salesBoard->slots()->find($slotId);
                    if ($slot) {
                        $newSlotId = $slot->id;
                    }
                }

                // Update Deal
                $deal->sales_board_slot_id = $newSlotId;
                $deal->slot_order = $item['order'];
                $deal->order = $item['order'];
                $deal->is_done = ($slotId === 'won');
                $deal->done_at = ($slotId === 'won') ? now() : null;
                $deal->save();
            }
        }

        // Nach Update State refresh
        $this->loadGroups();
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

        // Nach Update State refresh
        $this->loadGroups();
    }

    public function render()
    {
        return view('sales::livewire.board')->layout('platform::layouts.app');
    }
}
