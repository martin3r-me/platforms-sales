<?php

namespace Platform\Sales\Livewire\Settings\LostReason;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Str;
use Platform\Sales\Models\SalesDeal;
use Platform\Sales\Models\SalesLostReason;

/**
 * Stammdaten-Verwaltung der Verlustgründe (Lookup "lost_reasons").
 *
 * Generisch: jede Instanz pflegt ihre eigenen Gründe im UI – kein Hardcode,
 * kein MCP/Seeder nötig. Spiegelt das Settings-Muster aus dem organization-Modul.
 */
class Index extends Component
{
    public $search = '';
    public $showInactive = false;
    public $modalShow = false;
    public ?int $editingId = null;

    public $form = [
        'name' => '',
        'code' => '',
        'order' => 0,
        'is_active' => true,
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'showInactive' => ['except' => false],
    ];

    #[Computed]
    public function lostReasons()
    {
        $query = SalesLostReason::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('code', 'like', '%' . $this->search . '%');
            });
        }

        if (!$this->showInactive) {
            $query->active();
        }

        return $query->ordered()->get();
    }

    public function create()
    {
        $this->reset('form', 'editingId');
        $this->form['is_active'] = true;
        $this->resetValidation();
        $this->modalShow = true;
    }

    public function edit(int $id)
    {
        $reason = SalesLostReason::findOrFail($id);
        $this->editingId = $reason->id;
        $this->form = [
            'name' => $reason->name,
            'code' => $reason->code,
            'order' => (int) $reason->order,
            'is_active' => (bool) $reason->is_active,
        ];
        $this->resetValidation();
        $this->modalShow = true;
    }

    public function save()
    {
        $this->form['name'] = trim((string) $this->form['name']);

        // Code aus dem Namen ableiten, falls leer gelassen (lowercase snake)
        if (trim((string) $this->form['code']) === '') {
            $this->form['code'] = Str::slug($this->form['name'], '_');
        }

        $uniqueRule = 'unique:sales_lost_reasons,code' . ($this->editingId ? ',' . $this->editingId : '');

        $data = $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.code' => ['required', 'string', 'max:50', $uniqueRule],
            'form.order' => ['integer', 'min:0'],
            'form.is_active' => ['boolean'],
        ])['form'];

        if ($this->editingId) {
            SalesLostReason::findOrFail($this->editingId)->update($data);
            $message = 'Verlustgrund aktualisiert';
        } else {
            SalesLostReason::create($data);
            $message = 'Verlustgrund erstellt';
        }

        $this->modalShow = false;
        $this->reset('form', 'editingId');
        $this->dispatch('toast', message: $message);
    }

    public function toggleActive(int $id)
    {
        $reason = SalesLostReason::findOrFail($id);
        $reason->is_active = !$reason->is_active;
        $reason->save();
        $this->dispatch('toast', message: $reason->is_active ? 'Grund aktiviert' : 'Grund deaktiviert');
    }

    public function delete(int $id)
    {
        $reason = SalesLostReason::findOrFail($id);

        $inUse = SalesDeal::where('lost_reason_code', $reason->code)->count();
        if ($inUse > 0) {
            $this->dispatch('toast', type: 'error', message: "Wird noch von {$inUse} Deal(s) genutzt – bitte stattdessen deaktivieren.");
            return;
        }

        $reason->delete();
        $this->dispatch('toast', message: 'Verlustgrund gelöscht');
    }

    public function toggleInactive()
    {
        $this->showInactive = !$this->showInactive;
    }

    public function render()
    {
        return view('sales::livewire.settings.lost-reason.index')
            ->layout('platform::layouts.app');
    }
}
