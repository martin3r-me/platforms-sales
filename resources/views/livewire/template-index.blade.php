<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Sales', 'href' => route('sales.dashboard'), 'icon' => 'currency-euro'],
            ['label' => 'Templates'],
        ]">
            <x-nx-button variant="primary" size="sm" wire:click="createTemplate">
                <span class="flex items-center gap-2">
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    <span>Neues Template</span>
                </span>
            </x-nx-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container>
        @if($templates->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($templates as $template)
                    <x-nx-card hover>
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-[color:var(--nx-text)]">{{ $template->name }}</h3>
                                @if($template->is_system)
                                    <x-nx-badge variant="info">System-Template</x-nx-badge>
                                @else
                                    <x-nx-badge variant="neutral">Team-Template</x-nx-badge>
                                @endif
                            </div>
                            @if(!$template->is_system && $template->user_id === auth()->id())
                                <x-nx-button variant="danger" size="sm" wire:click="deleteTemplate({{ $template->id }})">
                                    @svg('heroicon-o-trash', 'w-4 h-4')
                                </x-nx-button>
                            @endif
                        </div>

                        @if($template->description)
                            <p class="text-[color:var(--nx-muted)] text-sm mb-4">{{ $template->description }}</p>
                        @endif

                        <!-- Template-Slots Vorschau -->
                        <div class="mb-4">
                            <h4 class="text-sm font-medium text-[color:var(--nx-text)] mb-2">Spalten:</h4>
                            <div class="flex flex-wrap gap-1">
                                @foreach($template->slots as $slot)
                                    @php
                                        $colorMap = [
                                            'blue' => 'info',
                                            'green' => 'success',
                                            'yellow' => 'warning',
                                            'red' => 'danger',
                                            'purple' => 'accent',
                                        ];
                                        $variant = $colorMap[$slot->color] ?? 'neutral';
                                    @endphp
                                    <x-nx-badge :variant="$variant">{{ $slot->name }}</x-nx-badge>
                                @endforeach
                            </div>
                        </div>

                        <!-- Aktionen -->
                        <div class="flex gap-2">
                            <x-nx-button variant="secondary" size="sm" href="{{ route('sales.templates.show', $template) }}" wire:navigate>
                                <span class="flex items-center gap-1">
                                    @svg('heroicon-o-eye', 'w-4 h-4')
                                    Anzeigen
                                </span>
                            </x-nx-button>
                            <x-nx-button variant="primary" size="sm" wire:click="createBoardFromTemplate({{ $template->id }})">
                                <span class="flex items-center gap-1">
                                    @svg('heroicon-o-plus', 'w-4 h-4')
                                    Board erstellen
                                </span>
                            </x-nx-button>
                        </div>
                    </x-nx-card>
                @endforeach
            </div>
        @else
            <x-nx-empty icon="heroicon-o-document-duplicate">
                Erstelle dein erstes Board-Template, um Boards schnell zu erstellen.
                <x-slot name="action">
                    <x-nx-button variant="primary" wire:click="createTemplate">
                        <span class="flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            Erstes Template erstellen
                        </span>
                    </x-nx-button>
                </x-slot>
            </x-nx-empty>
        @endif
    </x-ui-page-container>
</x-ui-page>
