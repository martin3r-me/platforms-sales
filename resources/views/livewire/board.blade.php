<div class="h-full d-flex">
    <!-- Info-Bereich -->
    <div class="w-80 border-r border-muted p-4 flex-shrink-0">
        <h3 class="text-lg font-semibold">{{ $salesBoard->name }}</h3>
        <div class="text-sm text-gray-600 mb-4">{{ $salesBoard->description ?? 'Keine Beschreibung' }}</div>
        
        <!-- Einfache Statistiken -->
        <div class="grid grid-cols-2 gap-2 mb-4">
            <div class="p-3 bg-blue-50 border border-blue-200 rounded">
                <div class="text-sm text-blue-600">Offene Deals</div>
                <div class="text-xl font-bold text-blue-800">{{ $groups->filter(fn($g) => !($g->isWonGroup ?? false))->sum(fn($g) => $g->deals->count()) }}</div>
            </div>
            <div class="p-3 bg-green-50 border border-green-200 rounded">
                <div class="text-sm text-green-600">Gewonnene Deals</div>
                <div class="text-xl font-bold text-green-800">{{ $groups->filter(fn($g) => $g->isWonGroup ?? false)->sum(fn($g) => $g->deals->count()) }}</div>
            </div>
        </div>
    </div>

    <!-- Kanban-Board -->
    <div class="flex-grow-1 overflow-hidden">
        <div class="h-full p-4">
            <h2 class="text-lg font-semibold mb-4">Kanban Board</h2>
            
            <!-- Einfache Spalten-Liste -->
            @foreach($groups as $group)
                <div class="border p-4 mb-4 rounded">
                    <h3>{{ $group->label }} ({{ $group->deals->count() }} Deals)</h3>
                    @if($group->deals->count() > 0)
                        @foreach($group->deals as $deal)
                            <div class="p-2 bg-gray-50 rounded mb-2">
                                {{ $deal->title }}
                            </div>
                        @endforeach
                    @else
                        <p class="text-gray-500 italic">Keine Deals</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>