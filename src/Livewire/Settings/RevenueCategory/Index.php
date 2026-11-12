<?php

namespace Platform\Sales\Livewire\Settings\RevenueCategory;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Str;
use Platform\Sales\Models\SalesRevenueCategory;

/**
 * Stammdaten-Verwaltung der Umsatzkategorien (Lookup "revenue_categories").
 *
 * Generisch: jede Instanz pflegt ihre eigenen Stufen im UI – kein Hardcode.
 * Spiegelt das Settings-Muster der Verlustgründe.
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
    public function categories()
    {
        $query = SalesRevenueCategory::query();

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
        $category = SalesRevenueCategory::findOrFail($id);
        $this->editingId = $category->id;
        $this->form = [
            'name' => $category->name,
            'code' => $category->code,
            'order' => (int) $category->order,
            'is_active' => (bool) $category->is_active,
        ];
        $this->resetValidation();
        $this->modalShow = true;
    }

    public function save()
    {
        $this->form['name'] = trim((string) $this->form['name']);

        // Code aus dem Namen ableiten, falls leer gelassen
        if (trim((string) $this->form['code']) === '') {
            $this->form['code'] = Str::slug($this->form['name'], '_');
        }

        $uniqueRule = 'unique:sales_revenue_categories,code' . ($this->editingId ? ',' . $this->editingId : '');

        $data = $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.code' => ['required', 'string', 'max:50', $uniqueRule],
            'form.order' => ['integer', 'min:0'],
            'form.is_active' => ['boolean'],
        ])['form'];

        if ($this->editingId) {
            SalesRevenueCategory::findOrFail($this->editingId)->update($data);
            $message = 'Umsatzkategorie aktualisiert';
        } else {
            SalesRevenueCategory::create($data);
            $message = 'Umsatzkategorie erstellt';
        }

        $this->modalShow = false;
        $this->reset('form', 'editingId');
        $this->dispatch('toast', message: $message);
    }

    public function toggleActive(int $id)
    {
        $category = SalesRevenueCategory::findOrFail($id);
        $category->is_active = !$category->is_active;
        $category->save();
        $this->dispatch('toast', message: $category->is_active ? 'Kategorie aktiviert' : 'Kategorie deaktiviert');
    }

    public function delete(int $id)
    {
        $category = SalesRevenueCategory::findOrFail($id);
        $category->delete();
        $this->dispatch('toast', message: 'Umsatzkategorie gelöscht');
    }

    public function toggleInactive()
    {
        $this->showInactive = !$this->showInactive;
    }

    public function render()
    {
        return view('sales::livewire.settings.revenue-category.index')
            ->layout('platform::layouts.app');
    }
}
