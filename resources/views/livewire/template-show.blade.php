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
            <x-ui-button variant="success" size="sm" wire:click="createBoardFromTemplate">
                <span class="flex items-center gap-2">
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    <span>Board erstellen</span>
                </span>
            </x-ui-button>
            @can('update', $template)
                <x-ui-button variant="primary" size="sm" wire:click="createSlot">
                    <span class="flex items-center gap-2">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span>Spalte hinzufügen</span>
                    </span>
                </x-ui-button>
            @endcan
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-6">
        <!-- Template Info Header -->
        <div class="flex items-center gap-3">
            @if($template->is_system)
                <x-ui-badge variant="primary" size="sm">System-Template</x-ui-badge>
            @else
                <x-ui-badge variant="neutral" size="sm">Team-Template</x-ui-badge>
            @endif
            @if($template->description)
                <span class="text-sm text-[var(--ui-muted)]">{{ $template->description }}</span>
            @endif
        </div>

        <!-- Template-Slots -->
        <div>
            <h2 class="text-xl font-semibold text-[var(--ui-secondary)] mb-4">Template-Spalten</h2>

            @if($slots->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($slots as $slot)
                        <div class="bg-[var(--ui-surface)] border border-[var(--ui-border)]/60 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex items-center gap-2">
                                    @php
                                        $slotColorMap = [
                                            'blue' => 'var(--ui-primary)',
                                            'green' => 'var(--ui-success)',
                                            'yellow' => 'var(--ui-warning)',
                                            'red' => 'var(--ui-danger)',
                                            'purple' => 'var(--ui-primary)',
                                            'orange' => 'var(--ui-warning)',
                                            'pink' => 'var(--ui-danger)',
                                            'indigo' => 'var(--ui-primary)',
                                        ];
                                        $dotColor = $slotColorMap[$slot->color] ?? 'var(--ui-muted)';
                                    @endphp
                                    <div class="w-4 h-4 rounded-full" style="background-color: {{ $dotColor }};"></div>
                                    <h3 class="font-medium text-[var(--ui-secondary)]">{{ $slot->name }}</h3>
                                </div>
                                @can('update', $template)
                                    <x-ui-button variant="danger" size="sm" wire:click="deleteSlot({{ $slot->id }})">
                                        @svg('heroicon-o-trash', 'w-4 h-4')
                                    </x-ui-button>
                                @endcan
                            </div>

                            @if($slot->description)
                                <p class="text-sm text-[var(--ui-muted)] mb-2">{{ $slot->description }}</p>
                            @endif

                            <div class="text-xs text-[var(--ui-muted)]">
                                Reihenfolge: {{ $slot->order }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-[var(--ui-muted-5)] rounded-full flex items-center justify-center mx-auto mb-4">
                        @svg('heroicon-o-view-columns', 'w-8 h-8 text-[var(--ui-muted)]')
                    </div>
                    <h3 class="text-lg font-medium text-[var(--ui-secondary)] mb-2">Keine Spalten vorhanden</h3>
                    <p class="text-[var(--ui-muted)] mb-4">Füge Spalten zu diesem Template hinzu.</p>
                    @can('update', $template)
                        <x-ui-button variant="primary" wire:click="createSlot">
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                Erste Spalte hinzufügen
                            </span>
                        </x-ui-button>
                    @endcan
                </div>
            @endif
        </div>

        <!-- Template-Info -->
        <div class="bg-[var(--ui-muted-5)] rounded-lg p-6">
            <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-4">Template-Informationen</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-[var(--ui-muted)]">Erstellt von</dt>
                    <dd class="text-sm text-[var(--ui-secondary)]">{{ $template->user->name ?? 'Unbekannt' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-[var(--ui-muted)]">Erstellt am</dt>
                    <dd class="text-sm text-[var(--ui-secondary)]">{{ $template->created_at->format('d.m.Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-[var(--ui-muted)]">Anzahl Spalten</dt>
                    <dd class="text-sm text-[var(--ui-secondary)]">{{ $slots->count() }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-[var(--ui-muted)]">Verwendet in Boards</dt>
                    <dd class="text-sm text-[var(--ui-secondary)]">{{ $template->boards->count() }}</dd>
                </div>
            </div>
        </div>
    </x-ui-page-container>
</x-ui-page>
