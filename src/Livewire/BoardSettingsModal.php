<?php

namespace Platform\Sales\Livewire;

use Livewire\Component;
use Platform\Sales\Models\SalesBoard;
use Platform\Sales\Models\SalesBoardTemplate;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class BoardSettingsModal extends Component
{
    public $modalShow = false;
    public $board;
    public $teamUsers = [];
    public $availableTemplates;

    public function rules(): array
    {
        return [
            'board.name' => 'required|string|max:255',
            'board.description' => 'nullable|string',
        ];
    }

    #[On('open-modal-board-settings')]
    public function openModal($boardId)
    {
        $this->board = SalesBoard::findOrFail($boardId);
        $this->authorize('update', $this->board);
        
        $this->teamUsers = Auth::user()
            ->currentTeam
            ->users()
            ->orderBy('name')
            ->get();
            
        $this->availableTemplates = SalesBoardTemplate::where('team_id', Auth::user()->currentTeam->id)
            ->orWhere('is_system', true)
            ->orderBy('name')
            ->get();
        
        $this->modalShow = true;
    }

    public function closeModal()
    {
        $this->modalShow = false;
        $this->reset(['board', 'teamUsers', 'availableTemplates']);
    }

    public function save()
    {
        $this->validate();
        $this->board->save();
        $this->closeModal();
        $this->dispatch('board-updated');
    }

    public function delete()
    {
        $this->authorize('delete', $this->board);
        $this->board->delete();
        $this->closeModal();
        $this->dispatch('board-deleted');
        return $this->redirect(route('sales.dashboard'), navigate: true);
    }

    public function render()
    {
        return view('sales::livewire.board-settings-modal');
    }
}