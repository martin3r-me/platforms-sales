{{-- resources/views/vendor/sales/livewire/sidebar-content.blade.php --}}
<div>
    {{-- Modul Header --}}
    <div x-show="!collapsed" class="p-3 text-sm italic text-[var(--nx-text)] uppercase border-b border-[var(--nx-line)] mb-2">
        Sales
    </div>

    {{-- Abschnitt: Allgemein (über UI-Komponenten) --}}
    <x-ui-sidebar-list label="Allgemein">
        <x-ui-sidebar-item :href="route('sales.dashboard')">
            @svg('heroicon-o-chart-bar', 'w-4 h-4 text-[var(--nx-text)]')
            <span class="ml-2 text-sm">Dashboard</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('sales.my-deals')">
            @svg('heroicon-o-rectangle-stack', 'w-4 h-4 text-[var(--nx-text)]')
            <span class="ml-2 text-sm">Meine Deals</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    {{-- Neues Sales Board --}}
    <x-ui-sidebar-list>
        <x-ui-sidebar-item wire:click="createSalesBoard">
            @svg('heroicon-o-plus-circle', 'w-4 h-4 text-[var(--nx-text)]')
            <span class="ml-2 text-sm">Sales Board anlegen</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    {{-- Collapsed: Icons-only für Allgemein --}}
    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--nx-line)]">
        <div class="flex flex-col gap-2">
            <a href="{{ route('sales.dashboard') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--nx-text)] hover:bg-[var(--nx-bg)]">
                @svg('heroicon-o-chart-bar', 'w-5 h-5')
            </a>
            <a href="{{ route('sales.my-deals') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--nx-text)] hover:bg-[var(--nx-bg)]">
                @svg('heroicon-o-rectangle-stack', 'w-5 h-5')
            </a>
        </div>
    </div>
    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--nx-line)]">
        <button type="button" wire:click="createSalesBoard" class="flex items-center justify-center p-2 rounded-md text-[var(--nx-text)] hover:bg-[var(--nx-bg)]">
            @svg('heroicon-o-plus-circle', 'w-5 h-5')
        </button>
    </div>

    {{-- Abschnitt: Sales Boards (nach Partner-Entity gruppiert, wie Planner) --}}
    <div>
        <div class="mt-2" x-show="!collapsed">
            {{-- 1. Nach EntityType → Entity-Baum → Boards --}}
            @foreach($entityTypeGroups as $typeGroup)
                <x-ui-sidebar-list wire:key="sales-type-group-{{ $typeGroup['type_id'] }}" :label="$typeGroup['type_name']">
                    @foreach($typeGroup['entities'] as $entityNode)
                        @include('sales::livewire.partials.sidebar-entity-node', [
                            'node' => $entityNode,
                            'typeIcon' => $typeGroup['type_icon'] ?? null,
                        ])
                    @endforeach
                </x-ui-sidebar-list>
            @endforeach

            {{-- 2. Boards ohne Partner-Entity --}}
            @if($unlinkedBoards->isNotEmpty())
                <x-ui-sidebar-list label="Ohne Partner">
                    @foreach($unlinkedBoards as $board)
                        <a wire:key="sales-unlinked-board-{{ $board->id }}"
                           href="{{ route('sales.boards.show', ['salesBoard' => $board]) }}"
                           wire:navigate
                           title="{{ $board->name }}"
                           class="flex items-center gap-1.5 py-0.5 pl-3 pr-2 text-[var(--nx-text)] hover:text-[var(--nx-accent)] transition truncate">
                            <span class="w-1 h-1 rounded-full flex-shrink-0 bg-[var(--nx-muted)] opacity-40"></span>
                            <span class="truncate text-[11px]">{{ $board->name }}</span>
                        </a>
                    @endforeach
                </x-ui-sidebar-list>
            @endif

            @if($entityTypeGroups->isEmpty() && $unlinkedBoards->isEmpty())
                <div class="px-3 py-1 text-xs text-[var(--nx-muted)]">
                    Keine Sales Boards
                </div>
            @endif
        </div>
    </div>
</div>
