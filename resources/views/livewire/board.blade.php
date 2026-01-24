<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$salesBoard->name" icon="heroicon-o-rectangle-stack" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Board-Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-4">
                <p class="text-sm text-[var(--ui-muted)]">Sidebar Content</p>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="true" storeKey="salesActivityOpen" side="right">
            <div class="p-4">
                <p class="text-sm text-[var(--ui-muted)]">Activity Content</p>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Leerer Content zum Testen --}}
    <div class="p-4">
        <p class="text-[var(--ui-muted)]">Board Content (leer zum Testen)</p>
    </div>
</x-ui-page>
