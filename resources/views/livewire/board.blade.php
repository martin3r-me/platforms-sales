<div class="h-full d-flex">
    <!-- Info-Bereich (fixe Breite) -->
    <div class="w-80 border-r border-muted p-4 flex-shrink-0">
        <!-- Board-Info -->
        <div class="mb-6">
            <div class="d-flex justify-between items-start mb-2">
                <h3 class="text-lg font-semibold">{{ $salesBoard->name }}</h3>
                <x-ui-button variant="info" size="sm" @click="$dispatch('open-modal-board-settings', { boardId: {{ $salesBoard->id }} })">
                    <div class="d-flex items-center gap-2">
                        @svg('heroicon-o-information-circle', 'w-4 h-4')
                        Info
                    </div>
                </x-ui-button>
            </div>
            <div class="text-sm text-gray-600 mb-4">{{ $salesBoard->description ?? 'Keine Beschreibung' }}</div>
            
            <!-- Einfache Statistiken -->
            <div class="grid grid-cols-2 gap-2 mb-4">
                <div class="p-3 bg-blue-50 border border-blue-200 rounded">
                    <div class="text-sm text-blue-600">Offene Deals</div>
                    <div class="text-xl font-bold text-blue-800">{{ $groups->filter(fn($g) => !($g->isWonGroup ?? false))->sum(fn($g) => $g->tasks->count()) }}</div>
                </div>
                <div class="p-3 bg-green-50 border border-green-200 rounded">
                    <div class="text-sm text-green-600">Gewonnene Deals</div>
                    <div class="text-xl font-bold text-green-800">{{ $groups->filter(fn($g) => $g->isWonGroup ?? false)->sum(fn($g) => $g->tasks->count()) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kanban-Board (nimmt Restplatz) -->
    <div class="flex-grow-1 overflow-hidden">
        <div class="h-full p-4">
            <h2 class="text-lg font-semibold mb-4">Kanban Board</h2>
            
            <!-- Debug Info (temporär) -->
            <div class="p-4 bg-yellow-100 border border-yellow-300 rounded mb-4">
                <h4 class="font-bold text-yellow-800">Debug Info:</h4>
                <p><strong>Groups Count:</strong> {{ $groups->count() }}</p>
                <p><strong>Groups Empty:</strong> {{ $groups->isEmpty() ? 'YES' : 'NO' }}</p>
                @if($groups->count() > 0)
                    <p><strong>First Group:</strong> {{ $groups->first()->name ?? 'NO NAME' }}</p>
                    <p><strong>First Group Tasks Count:</strong> {{ $groups->first()->tasks->count() }}</p>
                @endif
            </div>
        </div>
    </div>
</div>