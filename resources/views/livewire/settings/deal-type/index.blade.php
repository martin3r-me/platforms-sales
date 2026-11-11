<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Deal-Typen" icon="heroicon-o-tag" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Einstellungen" width="w-64" :defaultOpen="true" side="left">
            @include('sales::livewire.settings.partials.nav', ['aktiv' => 'deal-types'])
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Sales', 'href' => route('sales.dashboard'), 'icon' => 'chart-bar'],
            ['label' => 'Deal-Typen'],
        ]">
            <x-slot name="left">
                <div class="flex items-center gap-3">
                    <div class="w-56">
                        <x-nx-input-text name="search" size="sm" placeholder="Suchen…" wire:model.live.debounce.300ms="search" />
                    </div>
                    <x-nx-input-checkbox label="Inaktive" wire:model.live="showInactive" />
                </div>
            </x-slot>
            <x-nx-button variant="primary" size="sm" wire:click="create">
                @svg('heroicon-o-plus', 'w-4 h-4')
                <span>Neuer Typ</span>
            </x-nx-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container width="contained">
        <x-nx-card flush>
            @if ($this->types->isEmpty())
                <x-nx-empty icon="heroicon-o-tag">
                    Keine Deal-Typen gefunden.
                    <x-slot name="action">
                        <x-nx-button size="sm" wire:click="create">Typ anlegen</x-nx-button>
                    </x-slot>
                </x-nx-empty>
            @else
                <x-nx-table>
                    <x-nx-table-header>
                        <x-nx-table-header-cell>Name</x-nx-table-header-cell>
                        <x-nx-table-header-cell>Status</x-nx-table-header-cell>
                        <x-nx-table-header-cell align="right">Aktionen</x-nx-table-header-cell>
                    </x-nx-table-header>
                    <x-nx-table-body>
                        @foreach ($this->types as $type)
                            <x-nx-table-row wire:key="dt-{{ $type->id }}">
                                <x-nx-table-cell>
                                    <div class="font-medium text-[color:var(--nx-text)]">{{ $type->label }}</div>
                                    <div class="font-mono text-xs text-[color:var(--nx-faint)]">{{ $type->name }}</div>
                                </x-nx-table-cell>
                                <x-nx-table-cell>
                                    <x-nx-badge :variant="$type->is_active ? 'success' : 'neutral'" dot>
                                        {{ $type->is_active ? 'Aktiv' : 'Inaktiv' }}
                                    </x-nx-badge>
                                </x-nx-table-cell>
                                <x-nx-table-cell align="right">
                                    <div class="flex items-center justify-end gap-1">
                                        <x-nx-button variant="ghost" size="sm" wire:click="edit({{ $type->id }})">Bearbeiten</x-nx-button>
                                        <x-nx-button variant="ghost" size="sm" wire:click="toggleActive({{ $type->id }})">
                                            {{ $type->is_active ? 'Deaktivieren' : 'Aktivieren' }}
                                        </x-nx-button>
                                        <x-nx-button variant="danger" size="sm" wire:click="delete({{ $type->id }})" wire:confirm="Diesen Typ wirklich löschen?">
                                            @svg('heroicon-o-trash', 'w-4 h-4')
                                        </x-nx-button>
                                    </div>
                                </x-nx-table-cell>
                            </x-nx-table-row>
                        @endforeach
                    </x-nx-table-body>
                </x-nx-table>
            @endif
        </x-nx-card>
    </x-ui-page-container>

    <x-nx-modal size="md" wire:model="modalShow">
        <x-slot name="header">{{ $editingId ? 'Typ bearbeiten' : 'Neuer Deal-Typ' }}</x-slot>

        <form wire:submit.prevent="save" class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <x-nx-input-text name="label" label="Name" wire:model.live="form.label" errorKey="form.label" required placeholder="z. B. Rahmenvertrag" />
                <x-nx-input-number name="order" label="Sortierung" wire:model.live="form.order" min="0" />
            </div>
            <x-nx-input-text name="name" label="Code" wire:model.live="form.name" errorKey="form.name" placeholder="wird aus dem Namen abgeleitet, z. B. rahmenvertrag" />
            <x-nx-input-checkbox label="Aktiv" wire:model.live="form.is_active" />
        </form>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-nx-button variant="ghost" wire:click="$set('modalShow', false)">Abbrechen</x-nx-button>
                <x-nx-button variant="primary" wire:click="save">{{ $editingId ? 'Speichern' : 'Anlegen' }}</x-nx-button>
            </div>
        </x-slot>
    </x-nx-modal>
</x-ui-page>
