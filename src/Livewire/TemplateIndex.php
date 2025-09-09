<?php

namespace Platform\Sales\Livewire;

use Livewire\Component;
use Platform\Sales\Models\SalesBoardTemplate;
use Illuminate\Support\Facades\Auth;

class TemplateIndex extends Component
{
    public $templates;

    public function mount()
    {
        $this->loadTemplates();
    }

    public function loadTemplates()
    {
        $user = Auth::user();
        $teamId = $user->currentTeam->id;

        // Lade System-Templates und Team-Templates
        $this->templates = SalesBoardTemplate::where(function($query) use ($teamId) {
            $query->where('team_id', $teamId)
                  ->orWhere('is_system', true);
        })
        ->orderBy('is_system', 'desc')
        ->orderBy('name')
        ->get();
    }

    public function createTemplate()
    {
        $user = Auth::user();
        
        $template = new SalesBoardTemplate();
        $template->name = 'Neues Template';
        $template->description = 'Beschreibung für das neue Template';
        $template->user_id = $user->id;
        $template->team_id = $user->currentTeam->id;
        $template->is_system = false;
        $template->save();

        // Standard-Slots hinzufügen
        $defaultSlots = [
            ['name' => 'Neu', 'color' => 'blue'],
            ['name' => 'Erstkontakt', 'color' => 'yellow'],
            ['name' => 'Angebot', 'color' => 'orange'],
            ['name' => 'Verhandlung', 'color' => 'purple'],
        ];

        foreach ($defaultSlots as $index => $slotData) {
            $slot = new \Platform\Sales\Models\SalesBoardTemplateSlot();
            $slot->sales_board_template_id = $template->id;
            $slot->name = $slotData['name'];
            $slot->color = $slotData['color'];
            $slot->order = $index + 1;
            $slot->save();
        }

        $this->loadTemplates();
        $this->dispatch('template-created', templateId: $template->id);
    }

    public function deleteTemplate($templateId)
    {
        $template = SalesBoardTemplate::findOrFail($templateId);
        
        // System-Templates können nicht gelöscht werden
        if ($template->is_system) {
            $this->dispatch('template-error', message: 'System-Templates können nicht gelöscht werden.');
            return;
        }

        // Nur der Ersteller kann löschen
        if ($template->user_id !== Auth::id()) {
            $this->dispatch('template-error', message: 'Nur der Ersteller kann das Template löschen.');
            return;
        }

        $template->delete();
        $this->loadTemplates();
        $this->dispatch('template-deleted');
    }

    public function render()
    {
        return view('sales::livewire.template-index')->layout('platform::layouts.app');
    }
}
