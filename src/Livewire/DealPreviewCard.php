<?php

namespace Platform\Sales\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Sales\Models\SalesDeal;

class DealPreviewCard extends Component
{
    public SalesDeal $deal;

    public function render()
    {   
        return view('sales::livewire.deal-preview-card')->layout('platform::layouts.app');
    }
}
