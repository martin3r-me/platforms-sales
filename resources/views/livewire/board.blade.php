<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$salesBoard->name" icon="heroicon-o-folder" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Board-Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-4 space-y-6">
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Test</h3>
                    <div class="text-sm text-[var(--ui-secondary)]">Sidebar funktioniert</div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="true" storeKey="activityOpen" side="right">
            <div class="p-4 space-y-4">
                <div class="text-sm text-[var(--ui-muted)]">Activity Sidebar</div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <div class="flex-1 min-h-0 h-full flex items-center justify-center">
        <div class="text-lg text-[var(--ui-secondary)]">Board Content</div>
    </div>
</x-ui-page>
