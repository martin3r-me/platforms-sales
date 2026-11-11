<?php

namespace Platform\Sales\Livewire\Settings\DealSource;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Str;
use Platform\Sales\Models\SalesDeal;
use Platform\Sales\Models\SalesDealSource;

/**
 * Stammdaten-Verwaltung der Deal-Quellen (Lookup "sources").
 *
 * Generisch: jede Instanz pflegt ihre eigenen Quellen im UI – kein Hardcode.
 * Spiegelt das Settings-Muster der Verlustgründe.
 */
class Index extends Component
{
    public $search = '';
    public $showInactive = false;
    public $modalShow = false;
    public ?int $editingId = null;

    public $form = [
        'label' => '',
        'name' => '',
        'color' => 'blue',
        'order' => 0,
        'is_active' => true,
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'showInactive' => ['except' => false],
    ];

    #[Computed]
    public function sources()
    {
        $query = SalesDealSource::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('label', 'like', '%' . $this->search . '%')
                  ->orWhere('name', 'like', '%' . $this->search . '%');
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
        $this->form['color'] = 'blue';
        $this->form['is_active'] = true;
        $this->resetValidation();
        $this->modalShow = true;
    }

    public function edit(int $id)
    {
        $source = SalesDealSource::findOrFail($id);
        $this->editingId = $source->id;
        $this->form = [
            'label' => $source->label,
            'name' => $source->name,
            'color' => $source->color ?: 'blue',
            'order' => (int) $source->order,
            'is_active' => (bool) $source->is_active,
        ];
        $this->resetValidation();
        $this->modalShow = true;
    }

    public function save()
    {
        $this->form['label'] = trim((string) $this->form['label']);

        // Code aus dem Anzeigenamen ableiten, falls leer (lowercase snake)
        if (trim((string) $this->form['name']) === '') {
            $this->form['name'] = Str::slug($this->form['label'], '_');
        }

        $uniqueRule = 'unique:sales_deal_sources,name' . ($this->editingId ? ',' . $this->editingId : '');

        $data = $this->validate([
            'form.label' => ['required', 'string', 'max:255'],
            'form.name' => ['required', 'string', 'max:255', $uniqueRule],
            'form.color' => ['required', 'string', 'max:50'],
            'form.order' => ['integer', 'min:0'],
            'form.is_active' => ['boolean'],
        ])['form'];

        if ($this->editingId) {
            SalesDealSource::findOrFail($this->editingId)->update($data);
            $message = 'Deal-Quelle aktualisiert';
        } else {
            SalesDealSource::create($data);
            $message = 'Deal-Quelle erstellt';
        }

        $this->modalShow = false;
        $this->reset('form', 'editingId');
        $this->dispatch('toast', message: $message);
    }

    public function toggleActive(int $id)
    {
        $source = SalesDealSource::findOrFail($id);
        $source->is_active = !$source->is_active;
        $source->save();
        $this->dispatch('toast', message: $source->is_active ? 'Quelle aktiviert' : 'Quelle deaktiviert');
    }

    public function delete(int $id)
    {
        $source = SalesDealSource::findOrFail($id);

        $inUse = SalesDeal::where('sales_deal_source_id', $source->id)->count();
        if ($inUse > 0) {
            $this->dispatch('toast', type: 'error', message: "Wird noch von {$inUse} Deal(s) genutzt – bitte stattdessen deaktivieren.");
            return;
        }

        $source->delete();
        $this->dispatch('toast', message: 'Deal-Quelle gelöscht');
    }

    public function toggleInactive()
    {
        $this->showInactive = !$this->showInactive;
    }

    public function render()
    {
        return view('sales::livewire.settings.deal-source.index')
            ->layout('platform::layouts.app');
    }
}
