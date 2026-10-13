<?php

namespace Platform\Sales\Livewire;

use Livewire\Component;
use Platform\Sales\Models\SalesDeal;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Platform\Core\Contracts\CrmCompanyResolverInterface;
use Platform\Core\Contracts\CrmCompanyOptionsProviderInterface;
use Platform\Core\Contracts\CrmContactResolverInterface;
use Platform\Core\Contracts\CrmContactOptionsProviderInterface;

class CustomerDealSettingsModal extends Component
{
    public $modalShow = false;
    public $deal;
    public $companyId = null;
    public $companyDisplay = null;
    public $companyOptions = [];
    public $companySearch = '';
    
    public $contactId = null;
    public $contactDisplay = null;
    public $contactOptions = [];
    public $contactSearch = '';

    // Abnehmer als Organisation-Entity (optional, zusätzlich zur Company)
    public $entityId = null;
    public $entityDisplay = null;
    public $entityOptions = [];
    public $entitySearch = '';


    #[On('open-modal-customer-deal')]
    public function openModal($dealId)
    {
        $this->deal = SalesDeal::with(['companyLinks.company', 'contactLinks.contact'])->findOrFail($dealId);
        $this->authorize('update', $this->deal);
        
        // Load current company (first one if multiple)
        $currentCompany = $this->deal->companies()->first();
        $this->companyId = $currentCompany?->id;
        $this->resolveCompanyDisplay();
        $this->loadCompanyOptions('');
        
        // Load current contact (first one if multiple)
        $currentContact = $this->deal->contacts()->first();
        $this->contactId = $currentContact?->id;
        $this->resolveContactDisplay();
        $this->loadContactOptions('');

        // Load current Abnehmer-Entity
        $this->entityId = $this->deal->abnehmerEntity()?->id;
        $this->resolveEntityDisplay();
        $this->loadEntityOptions('');

        $this->modalShow = true;
    }

    public function closeModal()
    {
        $this->modalShow = false;
        $this->reset([
            'deal', 'companyId', 'companyDisplay', 'companyOptions', 'companySearch',
            'contactId', 'contactDisplay', 'contactOptions', 'contactSearch',
            'entityId', 'entityDisplay', 'entityOptions', 'entitySearch',
        ]);
    }

    public function render()
    {
        return view('sales::livewire.customer-deal-settings-modal');
    }

    // Company Methods
    public function updatedCompanyId($value)
    {
        $this->resolveCompanyDisplay();
    }

    public function updatedCompanySearch($value)
    {
        $this->loadCompanyOptions($this->companySearch);
    }

    private function resolveCompanyDisplay()
    {
        if ($this->companyId) {
            /** @var CrmCompanyResolverInterface $companyResolver */
            $companyResolver = app(CrmCompanyResolverInterface::class);
            $this->companyDisplay = $companyResolver->displayName($this->companyId);
        } else {
            $this->companyDisplay = null;
        }
    }

    private function loadCompanyOptions($search = '')
    {
        /** @var CrmCompanyOptionsProviderInterface $optionsProvider */
        $optionsProvider = app(CrmCompanyOptionsProviderInterface::class);
        $this->companyOptions = $optionsProvider->options($search);
    }

    // Contact Methods
    public function updatedContactId($value)
    {
        $this->resolveContactDisplay();
    }

    public function updatedContactSearch($value)
    {
        $this->loadContactOptions($this->contactSearch);
    }

    private function resolveContactDisplay()
    {
        if ($this->contactId) {
            /** @var CrmContactResolverInterface $contactResolver */
            $contactResolver = app(CrmContactResolverInterface::class);
            $this->contactDisplay = $contactResolver->displayName($this->contactId);
        } else {
            $this->contactDisplay = null;
        }
    }

    private function loadContactOptions($search = '')
    {
        /** @var CrmContactOptionsProviderInterface $optionsProvider */
        $optionsProvider = app(CrmContactOptionsProviderInterface::class);
        $this->contactOptions = $optionsProvider->options($search);
    }

    // Abnehmer-Entity (Organisation)
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
        $user = auth()->user();
        $baseTeam = $user?->currentTeamRelation;

        return $baseTeam ? $baseTeam->getRootTeam()->id : $user?->current_team_id;
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

    public function saveCompanyAndContact()
    {
        // Remove all existing links
        $this->deal->detachAllCompanies();
        $this->deal->detachAllContacts();

        // Add new company if selected
        if ($this->companyId) {
            $company = \Platform\Crm\Models\CrmCompany::find($this->companyId);
            if ($company) {
                $this->deal->attachCompany($company);
            }
        }

        // Add new contact if selected
        if ($this->contactId) {
            $contact = \Platform\Crm\Models\CrmContact::find($this->contactId);
            if ($contact) {
                $this->deal->attachContact($contact);
            }
        }

        // Abnehmer-Entity setzen/entfernen (DimensionLink dimension=entity)
        $this->deal->setAbnehmerEntity($this->entityId ? (int) $this->entityId : null);

        $this->closeModal();
        $this->dispatch('deal-updated');
    }
}
