<?php

namespace Platform\Sales\Livewire;

use Livewire\Component;
use Platform\Sales\Models\SalesBoard;
use Platform\Sales\Models\SalesBoardTemplate;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Platform\Core\Contracts\CrmCompanyResolverInterface;
use Platform\Core\Contracts\CrmCompanyOptionsProviderInterface;

class BoardSettingsModal extends Component
{
    public $modalShow = false;
    public $board;
    public $teamUsers = [];
    public $availableTemplates;

    // Partner-Company (EFP-Modell: ein Board pro Partner)
    public $partnerCompanyId = null;
    public $partnerCompanyDisplay = null;
    public $partnerCompanyOptions = [];
    public $partnerCompanySearch = '';

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

        // Aktuellen Partner laden
        $currentPartner = $this->board->partner();
        $this->partnerCompanyId = $currentPartner?->id;
        $this->resolvePartnerCompanyDisplay();
        $this->loadPartnerCompanyOptions('');

        $this->modalShow = true;
    }

    public function closeModal()
    {
        $this->modalShow = false;
        $this->reset([
            'board', 'teamUsers', 'availableTemplates',
            'partnerCompanyId', 'partnerCompanyDisplay', 'partnerCompanyOptions', 'partnerCompanySearch',
        ]);
    }

    public function updatedPartnerCompanyId($value)
    {
        $this->resolvePartnerCompanyDisplay();
    }

    public function updatedPartnerCompanySearch($value)
    {
        $this->loadPartnerCompanyOptions($this->partnerCompanySearch);
    }

    private function resolvePartnerCompanyDisplay()
    {
        if ($this->partnerCompanyId) {
            /** @var CrmCompanyResolverInterface $resolver */
            $resolver = app(CrmCompanyResolverInterface::class);
            $this->partnerCompanyDisplay = $resolver->displayName($this->partnerCompanyId);
        } else {
            $this->partnerCompanyDisplay = null;
        }
    }

    private function loadPartnerCompanyOptions($search = '')
    {
        /** @var CrmCompanyOptionsProviderInterface $optionsProvider */
        $optionsProvider = app(CrmCompanyOptionsProviderInterface::class);
        $this->partnerCompanyOptions = $optionsProvider->options($search);
    }

    public function save()
    {
        $this->validate();
        $this->board->save();

        // Partner-Company setzen/aktualisieren
        $company = $this->partnerCompanyId
            ? \Platform\Crm\Models\CrmCompany::find($this->partnerCompanyId)
            : null;
        $this->board->setPartner($company);

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