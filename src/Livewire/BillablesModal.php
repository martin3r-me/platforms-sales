<?php

namespace Platform\Sales\Livewire;

use Livewire\Component;
use Platform\Sales\Models\SalesDeal;
use Platform\Sales\Models\SalesDealBillable;

class BillablesModal extends Component
{
    public $deal = null;
    public $billables = [];
    public $showBillablesModal = false;

    protected $listeners = ['openBillablesModal' => 'openModal'];

    public function openModal($dealId)
    {
        $this->deal = SalesDeal::find($dealId);
        if ($this->deal) {
            $this->showBillablesModal = true;
            $this->loadBillables();
        }
    }

    public function closeModal()
    {
        $this->showBillablesModal = false;
    }

    public function loadBillables()
    {
        if ($this->deal) {
            $this->billables = $this->deal->billables->toArray();
        }
    }

    public function addBillable()
    {
        $this->billables[] = [
            'name' => '',
            'description' => '',
            'amount' => 0,
            'billing_type' => 'one_time',
            'billing_interval' => null,
            'duration_months' => null,
            'order' => count($this->billables) + 1,
            'is_active' => true,
        ];
    }

    public function removeBillable($index)
    {
        unset($this->billables[$index]);
        $this->billables = array_values($this->billables);
    }

    public function saveBillables()
    {
        if (!$this->deal) {
            return;
        }

        // Lösche alle bestehenden Billables
        $this->deal->billables()->delete();

        // Erstelle neue Billables
        foreach ($this->billables as $billableData) {
            if (!empty($billableData['name']) && $billableData['amount'] > 0) {
                SalesDealBillable::create([
                    'sales_deal_id' => $this->deal->id,
                    'name' => $billableData['name'],
                    'description' => $billableData['description'],
                    'amount' => $billableData['amount'],
                    'billing_type' => $billableData['billing_type'],
                    'billing_interval' => $billableData['billing_interval'],
                    'duration_months' => $billableData['duration_months'],
                    'order' => $billableData['order'],
                    'is_active' => $billableData['is_active'],
                ]);
            }
        }

        // Aktualisiere den Deal-Wert
        $this->deal->updateDealValueFromBillables();
        $this->deal->refresh();

        $this->closeModal();
        
        // Benachrichtige die Deal-Komponente
        $this->dispatch('billablesUpdated', $this->deal->id);
    }

    public function render()
    {
        return view('sales::livewire.billables-modal', [
            'deal' => $this->deal
        ]);
    }
}
