<div class="h-full overflow-y-auto p-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="d-flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Board-Templates</h1>
                <p class="text-gray-600">Verwalte Vorlagen für Vertriebsboards</p>
            </div>
            <x-ui-button variant="success" wire:click="createTemplate">
                <div class="d-flex items-center gap-2">
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    Neues Template
                </div>
            </x-ui-button>
        </div>
    </div>

    <!-- Templates Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($templates as $template)
            <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                <div class="d-flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $template->name }}</h3>
                        @if($template->is_system)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                System-Template
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                Team-Template
                            </span>
                        @endif
                    </div>
                    @if(!$template->is_system && $template->user_id === auth()->id())
                        <x-ui-button variant="danger" size="sm" wire:click="deleteTemplate({{ $template->id }})">
                            @svg('heroicon-o-trash', 'w-4 h-4')
                        </x-ui-button>
                    @endif
                </div>

                @if($template->description)
                    <p class="text-gray-600 text-sm mb-4">{{ $template->description }}</p>
                @endif

                <!-- Template-Slots Vorschau -->
                <div class="mb-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Spalten:</h4>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($template->slots as $slot)
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium"
                                  style="background-color: {{ $slot->color === 'blue' ? '#dbeafe' : ($slot->color === 'green' ? '#dcfce7' : ($slot->color === 'yellow' ? '#fef3c7' : ($slot->color === 'red' ? '#fee2e2' : '#f3e8ff'))) }}; color: {{ $slot->color === 'blue' ? '#1e40af' : ($slot->color === 'green' ? '#166534' : ($slot->color === 'yellow' ? '#92400e' : ($slot->color === 'red' ? '#991b1b' : '#6b21a8'))) }};">
                                {{ $slot->name }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <!-- Aktionen -->
                <div class="d-flex gap-2">
                    <x-ui-button variant="primary" size="sm" href="{{ route('sales.templates.show', $template) }}" wire:navigate>
                        <div class="d-flex items-center gap-1">
                            @svg('heroicon-o-eye', 'w-4 h-4')
                            Anzeigen
                        </div>
                    </x-ui-button>
                    <x-ui-button variant="success" size="sm" wire:click="createBoardFromTemplate({{ $template->id }})">
                        <div class="d-flex items-center gap-1">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            Board erstellen
                        </div>
                    </x-ui-button>
                </div>
            </div>
        @endforeach
    </div>

    @if($templates->count() === 0)
        <div class="text-center py-12">
            <div class="text-gray-500 mb-4">
                @svg('heroicon-o-document-duplicate', 'w-12 h-12 mx-auto')
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Keine Templates vorhanden</h3>
            <p class="text-gray-600 mb-4">Erstelle dein erstes Board-Template, um Boards schnell zu erstellen.</p>
            <x-ui-button variant="success" wire:click="createTemplate">
                <div class="d-flex items-center gap-2">
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    Erstes Template erstellen
                </div>
            </x-ui-button>
        </div>
    @endif
</div>
