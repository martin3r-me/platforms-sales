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

    // Partner als Organisation-Entity (ein Board pro Partner; wie Planner-Projekte
    // hängt das Board via DimensionLink an der Org-Entity).
    public $entityId = null;
    public $entityDisplay = null;
    public $entityOptions = [];
    public $entitySearch = '';

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

        // Aktuelle Partner-Entity laden
        $this->entityId = $this->board->partnerEntity()?->id;
        $this->resolveEntityDisplay();
        $this->loadEntityOptions('');

        $this->modalShow = true;
    }

    public function closeModal()
    {
        $this->modalShow = false;
        $this->reset([
            'board', 'teamUsers', 'availableTemplates',
            'entityId', 'entityDisplay', 'entityOptions', 'entitySearch',
        ]);
    }

    public function updatedEntityId($value)
    {
        $this->resolveEntityDisplay();
    }

    public function updatedEntitySearch($value)
    {
        $this->loadEntityOptions($this->entitySearch);
    }

    private function rootTeamId(): ?int
    {
        $user = Auth::user();
        $baseTeam = $user->currentTeamRelation;

        return $baseTeam ? $baseTeam->getRootTeam()->id : $user->current_team_id;
    }

    private function resolveEntityDisplay(): void
    {
        $this->entityDisplay = null;

        if ($this->entityId && class_exists(\Platform\Organization\Models\OrganizationEntity::class)) {
            $this->entityDisplay = \Platform\Organization\Models\OrganizationEntity::find($this->entityId)?->name;
        }
    }

    private function loadEntityOptions($search = ''): void
    {
        $this->entityOptions = [];

        if (! class_exists(\Platform\Organization\Models\OrganizationEntity::class)) {
            return;
        }

        $this->entityOptions = \Platform\Organization\Models\OrganizationEntity::query()
            ->where('team_id', $this->rootTeamId())
            ->where('is_active', true)
            ->when(trim((string) $search) !== '', fn ($q) => $q->where('name', 'like', '%' . trim($search) . '%'))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name'])
            ->map(fn ($e) => ['value' => $e->id, 'label' => $e->name])
            ->toArray();
    }

    public function save()
    {
        $this->validate();
        $this->board->save();

        // Partner-Entity setzen/aktualisieren (DimensionLink dimension=entity)
        $this->board->setPartnerEntity($this->entityId ? (int) $this->entityId : null);

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
