<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Verlustgründe" icon="heroicon-o-x-circle" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Einstellungen" width="w-64" :defaultOpen="true" side="left">
            @include('sales::livewire.settings.partials.nav', ['aktiv' => 'lost-reasons'])
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Sales', 'href' => route('sales.dashboard'), 'icon' => 'chart-bar'],
            ['label' => 'Verlustgründe'],
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
                <span>Neuer Grund</span>
            </x-nx-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container width="contained">
        <x-nx-card flush>
            @if ($this->lostReasons->isEmpty())
                <x-nx-empty icon="heroicon-o-x-circle">
                    Keine Verlustgründe gefunden.
                    <x-slot name="action">
                        <x-nx-button size="sm" wire:click="create">Grund anlegen</x-nx-button>
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
                        @foreach ($this->lostReasons as $reason)
                            <x-nx-table-row wire:key="lr-{{ $reason->id }}">
                                <x-nx-table-cell>
                                    <div class="font-medium text-[color:var(--nx-text)]">{{ $reason->name }}</div>
                                    <div class="font-mono text-xs text-[color:var(--nx-faint)]">{{ $reason->code }}</div>
                                </x-nx-table-cell>
                                <x-nx-table-cell>
                                    <x-nx-badge :variant="$reason->is_active ? 'success' : 'neutral'" dot>
                                        {{ $reason->is_active ? 'Aktiv' : 'Inaktiv' }}
                                    </x-nx-badge>
                                </x-nx-table-cell>
                                <x-nx-table-cell align="right">
                                    <div class="flex items-center justify-end gap-1">
                                        <x-nx-button variant="ghost" size="sm" wire:click="edit({{ $reason->id }})">Bearbeiten</x-nx-button>
                                        <x-nx-button variant="ghost" size="sm" wire:click="toggleActive({{ $reason->id }})">
                                            {{ $reason->is_active ? 'Deaktivieren' : 'Aktivieren' }}
                                        </x-nx-button>
                                        <x-nx-button variant="danger" size="sm" wire:click="delete({{ $reason->id }})" wire:confirm="Diesen Grund wirklich löschen?">
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
        <x-slot name="header">{{ $editingId ? 'Grund bearbeiten' : 'Neuer Verlustgrund' }}</x-slot>

        <form wire:submit.prevent="save" class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <x-nx-input-text name="name" label="Name" wire:model.live="form.name" errorKey="form.name" required placeholder="z. B. Listung abgelehnt" />
                <x-nx-input-number name="order" label="Sortierung" wire:model.live="form.order" min="0" />
            </div>
            <x-nx-input-text name="code" label="Code" wire:model.live="form.code" errorKey="form.code" placeholder="wird aus dem Namen abgeleitet, z. B. listung_abgelehnt" />
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
