<div class="h-full overflow-y-auto p-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="d-flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $template->name }}</h1>
                <p class="text-gray-600">{{ $template->description ?? 'Keine Beschreibung' }}</p>
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
            <div class="d-flex gap-2">
                <x-ui-button variant="success" wire:click="createBoardFromTemplate">
                    <div class="d-flex items-center gap-2">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        Board erstellen
                    </div>
                </x-ui-button>
                @can('update', $template)
                    <x-ui-button variant="primary" wire:click="createSlot">
                        <div class="d-flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            Spalte hinzufügen
                        </div>
                    </x-ui-button>
                @endcan
            </div>
        </div>
    </div>

    <!-- Template-Slots -->
    <div class="mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Template-Spalten</h2>
        
        @if($slots->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($slots as $slot)
                    <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="d-flex justify-between items-start mb-2">
                            <div class="d-flex items-center gap-2">
                                <div class="w-4 h-4 rounded-full" 
                                     style="background-color: {{ $slot->color === 'blue' ? '#3b82f6' : ($slot->color === 'green' ? '#10b981' : ($slot->color === 'yellow' ? '#f59e0b' : ($slot->color === 'red' ? '#ef4444' : '#8b5cf6'))) }};"></div>
                                <h3 class="font-medium text-gray-900">{{ $slot->name }}</h3>
                            </div>
                            @can('update', $template)
                                <x-ui-button variant="danger" size="sm" wire:click="deleteSlot({{ $slot->id }})">
                                    @svg('heroicon-o-trash', 'w-4 h-4')
                                </x-ui-button>
                            @endcan
                        </div>
                        
                        @if($slot->description)
                            <p class="text-sm text-gray-600 mb-2">{{ $slot->description }}</p>
                        @endif
                        
                        <div class="text-xs text-gray-500">
                            Reihenfolge: {{ $slot->order }}
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8">
                <div class="text-gray-500 mb-4">
                    @svg('heroicon-o-columns', 'w-12 h-12 mx-auto')
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Keine Spalten vorhanden</h3>
                <p class="text-gray-600 mb-4">Füge Spalten zu diesem Template hinzu.</p>
                @can('update', $template)
                    <x-ui-button variant="primary" wire:click="createSlot">
                        <div class="d-flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            Erste Spalte hinzufügen
                        </div>
                    </x-ui-button>
                @endcan
            </div>
        @endif
    </div>

    <!-- Template-Info -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Template-Informationen</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <dt class="text-sm font-medium text-gray-500">Erstellt von</dt>
                <dd class="text-sm text-gray-900">{{ $template->user->name ?? 'Unbekannt' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Erstellt am</dt>
                <dd class="text-sm text-gray-900">{{ $template->created_at->format('d.m.Y H:i') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Anzahl Spalten</dt>
                <dd class="text-sm text-gray-900">{{ $slots->count() }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Verwendet in Boards</dt>
                <dd class="text-sm text-gray-900">{{ $template->boards->count() }}</dd>
            </div>
        </div>
    </div>
</div>
