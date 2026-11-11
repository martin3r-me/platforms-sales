<?php

namespace Platform\Sales\Livewire\Settings\DealType;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Str;
use Platform\Sales\Models\SalesDeal;
use Platform\Sales\Models\SalesDealType;

/**
 * Stammdaten-Verwaltung der Deal-Typen (Lookup "types").
 *
 * Generisch: jede Instanz pflegt ihre eigenen Typen im UI – kein Hardcode.
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
    public function types()
    {
        $query = SalesDealType::query();

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
        $type = SalesDealType::findOrFail($id);
        $this->editingId = $type->id;
        $this->form = [
            'label' => $type->label,
            'name' => $type->name,
            'color' => $type->color ?: 'blue',
            'order' => (int) $type->order,
            'is_active' => (bool) $type->is_active,
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

        $uniqueRule = 'unique:sales_deal_types,name' . ($this->editingId ? ',' . $this->editingId : '');

        $data = $this->validate([
            'form.label' => ['required', 'string', 'max:255'],
            'form.name' => ['required', 'string', 'max:255', $uniqueRule],
            'form.color' => ['required', 'string', 'max:50'],
            'form.order' => ['integer', 'min:0'],
            'form.is_active' => ['boolean'],
        ])['form'];

        if ($this->editingId) {
            SalesDealType::findOrFail($this->editingId)->update($data);
            $message = 'Deal-Typ aktualisiert';
        } else {
            SalesDealType::create($data);
            $message = 'Deal-Typ erstellt';
        }

        $this->modalShow = false;
        $this->reset('form', 'editingId');
        $this->dispatch('toast', message: $message);
    }

    public function toggleActive(int $id)
    {
        $type = SalesDealType::findOrFail($id);
        $type->is_active = !$type->is_active;
        $type->save();
        $this->dispatch('toast', message: $type->is_active ? 'Typ aktiviert' : 'Typ deaktiviert');
    }

    public function delete(int $id)
    {
        $type = SalesDealType::findOrFail($id);

        $inUse = SalesDeal::where('sales_deal_type_id', $type->id)->count();
        if ($inUse > 0) {
            $this->dispatch('toast', type: 'error', message: "Wird noch von {$inUse} Deal(s) genutzt – bitte stattdessen deaktivieren.");
            return;
        }

        $type->delete();
        $this->dispatch('toast', message: 'Deal-Typ gelöscht');
    }

    public function toggleInactive()
    {
        $this->showInactive = !$this->showInactive;
    }

    public function render()
    {
        return view('sales::livewire.settings.deal-type.index')
            ->layout('platform::layouts.app');
    }
}
