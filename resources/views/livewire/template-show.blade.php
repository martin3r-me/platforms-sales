<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Sales', 'href' => route('sales.dashboard'), 'icon' => 'currency-euro'],
            ['label' => 'Templates', 'href' => route('sales.templates.index')],
            ['label' => $template->name],
        ]">
            <x-nx-button variant="primary" size="sm" wire:click="createBoardFromTemplate">
                <span class="flex items-center gap-2">
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    <span>Board erstellen</span>
                </span>
            </x-nx-button>
            @can('update', $template)
                <x-nx-button variant="secondary" size="sm" wire:click="createSlot">
                    <span class="flex items-center gap-2">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span>Spalte hinzufügen</span>
                    </span>
                </x-nx-button>
            @endcan
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-6">
        <!-- Template Info Header -->
        <div class="flex items-center gap-3">
            @if($template->is_system)
                <x-nx-badge variant="info">System-Template</x-nx-badge>
            @else
                <x-nx-badge variant="neutral">Team-Template</x-nx-badge>
            @endif
            @if($template->description)
                <span class="text-sm text-[color:var(--nx-muted)]">{{ $template->description }}</span>
            @endif
        </div>

        <!-- Template-Slots -->
        <div>
            <h2 class="text-xl font-semibold text-[color:var(--nx-text)] mb-4">Template-Spalten</h2>

            @if($slots->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($slots as $slot)
                        <x-nx-card>
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex items-center gap-2">
                                    @php
                                        $slotColorMap = [
                                            'blue' => 'var(--nx-info)',
                                            'green' => 'var(--nx-success)',
                                            'yellow' => 'var(--nx-warning)',
                                            'red' => 'var(--nx-danger)',
                                            'purple' => 'var(--nx-accent)',
                                            'orange' => 'var(--nx-warning)',
                                            'pink' => 'var(--nx-danger)',
                                            'indigo' => 'var(--nx-accent)',
                                        ];
                                        $dotColor = $slotColorMap[$slot->color] ?? 'var(--nx-muted)';
                                    @endphp
                                    <div class="w-4 h-4 rounded-full" style="background-color: {{ $dotColor }};"></div>
                                    <h3 class="font-medium text-[color:var(--nx-text)]">{{ $slot->name }}</h3>
                                </div>
                                @can('update', $template)
                                    <x-nx-button variant="danger" size="sm" wire:click="deleteSlot({{ $slot->id }})">
                                        @svg('heroicon-o-trash', 'w-4 h-4')
                                    </x-nx-button>
                                @endcan
                            </div>

                            @if($slot->description)
                                <p class="text-sm text-[color:var(--nx-muted)] mb-2">{{ $slot->description }}</p>
                            @endif

                            <div class="text-xs text-[color:var(--nx-muted)]">
                                Reihenfolge: {{ $slot->order }}
                            </div>
                        </x-nx-card>
                    @endforeach
                </div>
            @else
                <x-nx-empty icon="heroicon-o-view-columns">
                    Füge Spalten zu diesem Template hinzu.
                    @can('update', $template)
                        <x-slot name="action">
                            <x-nx-button variant="primary" wire:click="createSlot">
                                <span class="flex items-center gap-2">
                                    @svg('heroicon-o-plus', 'w-4 h-4')
                                    Erste Spalte hinzufügen
                                </span>
                            </x-nx-button>
                        </x-slot>
                    @endcan
                </x-nx-empty>
            @endif
        </div>

        <!-- Template-Info -->
        <x-nx-card>
            <h3 class="text-lg font-semibold text-[color:var(--nx-text)] mb-4">Template-Informationen</h3>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-[color:var(--nx-muted)]">Erstellt von</dt>
                    <dd class="text-sm text-[color:var(--nx-text)]">{{ $template->user->name ?? 'Unbekannt' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-[color:var(--nx-muted)]">Erstellt am</dt>
                    <dd class="text-sm text-[color:var(--nx-text)]">{{ $template->created_at->format('d.m.Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-[color:var(--nx-muted)]">Anzahl Spalten</dt>
                    <dd class="text-sm text-[color:var(--nx-text)]">{{ $slots->count() }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-[color:var(--nx-muted)]">Verwendet in Boards</dt>
                    <dd class="text-sm text-[color:var(--nx-text)]">{{ $template->boards->count() }}</dd>
                </div>
            </dl>
        </x-nx-card>
    </x-ui-page-container>
</x-ui-page>
