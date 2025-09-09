<div class="space-y-2">
    <!-- Dynamische Sales Boards -->
    @foreach($salesBoards as $board)
        <a href="{{ route('sales.boards.show', $board) }}" 
           class="block p-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md transition-colors"
           wire:navigate>
            <div class="d-flex items-center gap-2">
                @svg('heroicon-o-folder', 'w-4 h-4 text-gray-500')
                <span class="truncate">{{ $board->name }}</span>
            </div>
        </a>
    @endforeach
</div>