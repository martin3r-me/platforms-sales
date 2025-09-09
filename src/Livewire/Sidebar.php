<?php

namespace Platform\Sales\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Sales\Models\SalesBoard;
use Platform\Sales\Models\SalesBoardSlot;
use Platform\Sales\Models\SalesBoardTemplate;
use Livewire\Attributes\On; 

class Sidebar extends Component
{
    #[On('updateSidebar')] 
    public function updateSidebar()
    {
        
    }

    #[On('create-sales-board')]
    public function createSalesBoard()
    {
        $user = Auth::user();
        $teamId = $user->currentTeam->id;

        // 1. Neues Sales Board anlegen
        $board = new SalesBoard();
        $board->name = 'Neues Vertriebsboard';
        $board->user_id = $user->id;
        $board->team_id = $teamId;
        $board->order = SalesBoard::where('team_id', $teamId)->max('order') + 1;
        $board->save();

        // 2. Standard-Slots anlegen: Neu, Erstkontakt, Angebot, Verhandlung
        $defaultSlots = [
            ['name' => 'Neu', 'color' => 'blue'],
            ['name' => 'Erstkontakt', 'color' => 'yellow'],
            ['name' => 'Angebot', 'color' => 'orange'],
            ['name' => 'Verhandlung', 'color' => 'purple'],
        ];
        foreach ($defaultSlots as $index => $slotData) {
            SalesBoardSlot::create([
                'sales_board_id' => $board->id,
                'name' => $slotData['name'],
                'color' => $slotData['color'],
                'order' => $index + 1,
            ]);
        }

        return redirect()->route('sales.boards.show', ['salesBoard' => $board->id]);
    }

    #[On('create-board-from-template')]
    public function createBoardFromTemplate($templateId)
    {
        $user = Auth::user();
        $template = SalesBoardTemplate::findOrFail($templateId);
        
        // Prüfe Berechtigung
        if (!$template->is_system && $template->team_id !== $user->currentTeam->id) {
            return;
        }

        $board = $template->createBoard([
            'name' => $template->name . ' - ' . now()->format('d.m.Y'),
            'description' => $template->description,
        ]);

        return redirect()->route('sales.boards.show', ['salesBoard' => $board->id]);
    }

    public function render()
    {
        // Dynamische Sales Boards holen, z. B. team-basiert
        $salesBoards = SalesBoard::query()
            ->where('team_id', auth()->user()?->currentTeam->id ?? null)
            ->orderBy('name')
            ->get();

        return view('sales::livewire.sidebar', [
            'salesBoards' => $salesBoards,
        ]);
    }
}
