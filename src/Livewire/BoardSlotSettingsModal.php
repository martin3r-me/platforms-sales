<?php

namespace Platform\Sales\Livewire;

use Livewire\Component;
use Platform\Sales\Models\SalesBoardSlot;
use Livewire\Attributes\On;

class BoardSlotSettingsModal extends Component
{
    public $modalShow = false;
    public $slot;

    public function rules(): array
    {
        return [
            'slot.name' => 'required|string|max:255',
            'slot.description' => 'nullable|string',
            'slot.color' => 'required|string|in:blue,green,yellow,red,purple,orange,pink,indigo',
        ];
    }

    #[On('open-modal-board-slot-settings')]
    public function openModal($boardSlotId)
    {
        $this->slot = SalesBoardSlot::findOrFail($boardSlotId);
        $this->authorize('update', $this->slot->salesBoard);
        $this->modalShow = true;
    }

    public function closeModal()
    {
        $this->modalShow = false;
        $this->reset(['slot']);
    }

    public function save()
    {
        $this->validate();
        $this->slot->save();
        $this->closeModal();
        $this->dispatch('slot-updated');
    }

    public function delete()
    {
        $this->authorize('update', $this->slot->salesBoard);
        $this->slot->delete();
        $this->closeModal();
        $this->dispatch('slot-deleted');
    }

    public function render()
    {
        return view('sales::livewire.board-slot-settings-modal');
    }
}