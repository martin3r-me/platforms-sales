<?php

namespace Platform\Sales\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Sales\Models\SalesDeal;

class Deal extends Component
{
    public $deal;

    protected $rules = [
        'deal.title' => 'required|string|max:255',
        'deal.description' => 'nullable|string',

        'deal.is_done' => 'boolean',
        'deal.due_date' => 'nullable|date',
        'deal.user_in_charge_id' => 'nullable|integer',
        'deal.deal_value' => 'nullable|numeric|min:0',
        'deal.probability_percent' => 'nullable|integer|min:0|max:100',
        'deal.deal_source' => 'nullable|string',
        'deal.deal_type' => 'nullable|string',
        'deal.sales_board_id' => 'nullable|integer',
    ];

    public function mount(SalesDeal $salesDeal)
    {
        $this->authorize('view', $salesDeal);
        $this->deal = $salesDeal;
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
