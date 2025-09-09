<?php

namespace Platform\Sales\Livewire;

use Livewire\Component;
use Platform\Sales\Models\SalesBoardTemplate;
use Illuminate\Support\Facades\Auth;

class TemplateShow extends Component
{
    public SalesBoardTemplate $template;
    public $slots;

    public function mount(SalesBoardTemplate $template)
    {
        $this->authorize('view', $template);
        $this->template = $template;
        $this->loadSlots();
    }

    public function loadSlots()
    {
        $this->slots = $this->template->slots()->orderBy('order')->get();
    }

    public function createSlot()
    {
        $this->authorize('update', $this->template);

        $slot = new \Platform\Sales\Models\SalesBoardTemplateSlot();
        $slot->sales_board_template_id = $this->template->id;
        $slot->name = 'Neue Spalte';
        $slot->color = 'blue';
        $slot->order = $this->slots->count() + 1;
        $slot->save();

        $this->loadSlots();
        $this->dispatch('slot-created', slotId: $slot->id);
    }

    public function updateSlotOrder($slots)
    {
        $this->authorize('update', $this->template);

        foreach ($slots as $slotData) {
            $slot = \Platform\Sales\Models\SalesBoardTemplateSlot::find($slotData['value']);
            if ($slot) {
                $slot->order = $slotData['order'];
                $slot->save();
            }
        }

        $this->loadSlots();
    }

    public function deleteSlot($slotId)
    {
        $this->authorize('update', $this->template);

        $slot = \Platform\Sales\Models\SalesBoardTemplateSlot::findOrFail($slotId);
        $slot->delete();

        $this->loadSlots();
        $this->dispatch('slot-deleted');
    }

    public function createBoardFromTemplate()
    {
        $this->authorize('view', $this->template);

        $board = $this->template->createBoard([
            'name' => $this->template->name . ' - ' . now()->format('d.m.Y'),
            'description' => $this->template->description,
        ]);

        return $this->redirect(route('sales.boards.show', $board), navigate: true);
    }

    public function render()
    {
        return view('sales::livewire.template-show')->layout('platform::layouts.app');
    }
}
