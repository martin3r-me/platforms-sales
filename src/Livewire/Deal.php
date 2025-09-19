<?php

namespace Platform\Sales\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Sales\Models\SalesDeal;
use Platform\Sales\Models\SalesDealSource;
use Platform\Sales\Models\SalesDealType;
use Platform\Sales\Models\SalesPriority;

class Deal extends Component
{
    public $deal;
    public $dealSources;
    public $dealTypes;
    public $priorities;

    protected $rules = [
        'deal.title' => 'required|string|max:255',
        'deal.description' => 'nullable|string',

        'deal.is_done' => 'boolean',
        'deal.due_date' => 'nullable|date',
        'deal.user_in_charge_id' => 'nullable|integer',
        'deal.deal_value' => 'nullable|numeric|min:0',
        'deal.probability_percent' => 'nullable|integer|min:0|max:100',
        'deal.sales_deal_source_id' => 'nullable|integer|exists:sales_deal_sources,id',
        'deal.sales_deal_type_id' => 'nullable|integer|exists:sales_deal_types,id',
        'deal.sales_priority_id' => 'nullable|integer|exists:sales_priorities,id',
        'deal.billing_interval' => 'nullable|in:one_time,monthly,quarterly,yearly',
        'deal.billing_duration_months' => 'nullable|integer|min:1',
        'deal.monthly_recurring_value' => 'nullable|numeric|min:0',
        'deal.sales_board_id' => 'nullable|integer',
    ];

    public function mount(SalesDeal $salesDeal)
    {
        $this->authorize('view', $salesDeal);
        $this->deal = $salesDeal;
        
        // Lookup-Daten laden
        $this->dealSources = SalesDealSource::active()->ordered()->get();
        $this->dealTypes = SalesDealType::active()->ordered()->get();
        $this->priorities = SalesPriority::active()->ordered()->get();
    }

    public function rendered()
    {
        $this->dispatch('comms', [
            'model' => get_class($this->deal),                                // z. B. 'Platform\Sales\Models\SalesDeal'
            'modelId' => $this->deal->id,
            'subject' => $this->deal->title,
            'description' => $this->deal->description ?? '',
            'url' => route('sales.deals.show', $this->deal),            // absolute URL zum Deal
            'source' => 'sales.deal.view',                                // eindeutiger Quell-Identifier (frei wählbar)
            'recipients' => [$this->deal->user_in_charge_id],                // falls vorhanden, sonst leer
            'meta' => [
                'deal_value' => $this->deal->deal_value,
                'probability_percent' => $this->deal->probability_percent,
                'due_date' => $this->deal->due_date,
                'deal_source' => $this->deal->deal_source,
                'deal_type' => $this->deal->deal_type,
            ],
        ]);
    }

    public function updatedDeal($property, $value)
    {
        $this->validateOnly("deal.$property");
        
        // Automatische Berechnung des Gesamtwerts bei wiederkehrenden Deals
        if (in_array($property, ['monthly_recurring_value', 'billing_duration_months']) && 
            $this->deal->billing_interval && 
            $this->deal->billing_interval !== 'one_time' &&
            $this->deal->monthly_recurring_value && 
            $this->deal->billing_duration_months) {
            
            $this->deal->deal_value = (float) $this->deal->monthly_recurring_value * (int) $this->deal->billing_duration_months;
        }
        
        $this->deal->save();
    }

    public function deleteDeal()
    {
        $this->deal->delete();
        return $this->redirect('/', navigate: true);
    }

    public function deleteDealAndReturnToDashboard()
    {
        $this->authorize('delete', $this->deal);
        $this->deal->delete();
        return $this->redirect(route('sales.my-deals'), navigate: true);
    }

    public function deleteDealAndReturnToBoard()
    {
        $this->authorize('delete', $this->deal);
        
        if (!$this->deal->salesBoard) {
            // Fallback zu MyDeals wenn kein Board vorhanden
            $this->deal->delete();
            return $this->redirect(route('sales.my-deals'), navigate: true);
        }
        
        $this->deal->delete();
        return $this->redirect(route('sales.boards.show', $this->deal->salesBoard), navigate: true);
    }

    public function render()
    {        
        // Teammitglieder für Zuweisung laden
        $teamUsers = Auth::user()
            ->currentTeam
            ->users()
            ->orderBy('name')
            ->get();
            
        return view('sales::livewire.deal', [
            'teamUsers' => $teamUsers,
        ])->layout('platform::layouts.app');
    }
}
