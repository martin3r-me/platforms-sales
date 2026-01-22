{{-- resources/views/vendor/sales/livewire/sidebar-content.blade.php --}}
<div>
    {{-- Modul Header --}}
    <div x-show="!collapsed" class="p-3 text-sm italic text-[var(--ui-secondary)] uppercase border-b border-[var(--ui-border)] mb-2">
        Sales
    </div>
    
    {{-- Abschnitt: Allgemein (über UI-Komponenten) --}}
    <x-ui-sidebar-list label="Allgemein">
        <x-ui-sidebar-item :href="route('sales.dashboard')">
            @svg('heroicon-o-chart-bar', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Dashboard</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('sales.my-deals')">
            @svg('heroicon-o-rectangle-stack', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Meine Deals</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    {{-- Neues Sales Board --}}
    <x-ui-sidebar-list>
        <x-ui-sidebar-item wire:click="createSalesBoard">
            @svg('heroicon-o-plus-circle', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Sales Board anlegen</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    {{-- Collapsed: Icons-only für Allgemein --}}
    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--ui-border)]">
        <div class="flex flex-col gap-2">
            <a href="{{ route('sales.dashboard') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-chart-bar', 'w-5 h-5')
            </a>
            <a href="{{ route('sales.my-deals') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-rectangle-stack', 'w-5 h-5')
            </a>
        </div>
    </div>
    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--ui-border)]">
        <button type="button" wire:click="createSalesBoard" class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
            @svg('heroicon-o-plus-circle', 'w-5 h-5')
        </button>
    </div>

    {{-- Abschnitt: Sales Boards --}}
    <div>
        <div class="mt-2" x-show="!collapsed">
            @if($salesBoards->isNotEmpty())
                <x-ui-sidebar-list label="Sales Boards">
                    @foreach($salesBoards as $board)
                        <x-ui-sidebar-item :href="route('sales.boards.show', ['salesBoard' => $board])">
                            @svg('heroicon-o-folder', 'w-5 h-5 flex-shrink-0 text-[var(--ui-secondary)]')
                            <div class="flex-1 min-w-0 ml-2">
                                <div class="truncate text-sm font-medium">{{ $board->name }}</div>
                            </div>
                        </x-ui-sidebar-item>
                    @endforeach
                </x-ui-sidebar-list>
            @else
                <div class="px-3 py-1 text-xs text-[var(--ui-muted)]">
                    Keine Sales Boards
                </div>
            @endif
        </div>
    </div>
</div>