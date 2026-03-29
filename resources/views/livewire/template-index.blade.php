<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Sales', 'href' => route('sales.dashboard'), 'icon' => 'currency-euro'],
            ['label' => 'Templates'],
        ]">
            <x-ui-button variant="success" size="sm" wire:click="createTemplate">
                <span class="flex items-center gap-2">
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    <span>Neues Template</span>
                </span>
            </x-ui-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container>
        @if($templates->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($templates as $template)
                    <div class="bg-[var(--ui-surface)] border border-[var(--ui-border)]/60 rounded-lg p-6 hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-[var(--ui-secondary)]">{{ $template->name }}</h3>
                                @if($template->is_system)
                                    <x-ui-badge variant="primary" size="sm">System-Template</x-ui-badge>
                                @else
                                    <x-ui-badge variant="neutral" size="sm">Team-Template</x-ui-badge>
                                @endif
                            </div>
                            @if(!$template->is_system && $template->user_id === auth()->id())
                                <x-ui-button variant="danger" size="sm" wire:click="deleteTemplate({{ $template->id }})">
                                    @svg('heroicon-o-trash', 'w-4 h-4')
                                </x-ui-button>
                            @endif
                        </div>

                        @if($template->description)
                            <p class="text-[var(--ui-muted)] text-sm mb-4">{{ $template->description }}</p>
                        @endif

                        <!-- Template-Slots Vorschau -->
                        <div class="mb-4">
                            <h4 class="text-sm font-medium text-[var(--ui-secondary)] mb-2">Spalten:</h4>
                            <div class="flex flex-wrap gap-1">
                                @foreach($template->slots as $slot)
                                    @php
                                        $colorMap = [
                                            'blue' => 'primary',
                                            'green' => 'success',
                                            'yellow' => 'warning',
                                            'red' => 'danger',
                                            'purple' => 'primary',
                                        ];
                                        $variant = $colorMap[$slot->color] ?? 'neutral';
                                    @endphp
                                    <x-ui-badge :variant="$variant" size="sm">{{ $slot->name }}</x-ui-badge>
                                @endforeach
                            </div>
                        </div>

                        <!-- Aktionen -->
                        <div class="flex gap-2">
                            <x-ui-button variant="primary" size="sm" href="{{ route('sales.templates.show', $template) }}" wire:navigate>
                                <span class="flex items-center gap-1">
                                    @svg('heroicon-o-eye', 'w-4 h-4')
                                    Anzeigen
                                </span>
                            </x-ui-button>
                            <x-ui-button variant="success" size="sm" wire:click="createBoardFromTemplate({{ $template->id }})">
                                <span class="flex items-center gap-1">
                                    @svg('heroicon-o-plus', 'w-4 h-4')
                                    Board erstellen
                                </span>
                            </x-ui-button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <div class="w-16 h-16 bg-[var(--ui-muted-5)] rounded-full flex items-center justify-center mx-auto mb-4">
                    @svg('heroicon-o-document-duplicate', 'w-8 h-8 text-[var(--ui-muted)]')
                </div>
                <h3 class="text-lg font-medium text-[var(--ui-secondary)] mb-2">Keine Templates vorhanden</h3>
                <p class="text-[var(--ui-muted)] mb-4">Erstelle dein erstes Board-Template, um Boards schnell zu erstellen.</p>
                <x-ui-button variant="success" wire:click="createTemplate">
                    <span class="flex items-center gap-2">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        Erstes Template erstellen
                    </span>
                </x-ui-button>
            </div>
        @endif
    </x-ui-page-container>
</x-ui-page>
