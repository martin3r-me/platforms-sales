<?php

namespace Platform\Sales\Livewire;

use Livewire\Component;
use Platform\Sales\Models\SalesDeal;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Platform\Core\Contracts\CrmCompanyResolverInterface;
use Platform\Core\Contracts\CrmCompanyOptionsProviderInterface;

class CustomerDealSettingsModal extends Component
{
    public $modalShow = false;
    public $deal;
    public $companyId = null;
    public $companyDisplay = null;
    public $companyOptions = [];
    public $companySearch = '';
    

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
        
        
        $this->modalShow = true;
    }

    public function closeModal()
    {
        $this->modalShow = false;
        $this->reset(['deal', 'companyId', 'companyDisplay', 'companyOptions', 'companySearch']);
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

    public function saveCompany()
    {
        // Remove all existing company links
        $this->deal->detachAllCompanies();

        // Add new company if selected
        if ($this->companyId) {
            $company = \Platform\Crm\Models\CrmCompany::find($this->companyId);
            if ($company) {
                $this->deal->attachCompany($company);
            }
        }

        $this->closeModal();
        $this->dispatch('deal-updated');
    }
}
