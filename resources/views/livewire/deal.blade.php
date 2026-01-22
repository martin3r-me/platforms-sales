<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$deal->title" icon="heroicon-o-document-text">
            @if($deal->is_done)
                <x-ui-badge variant="success" size="sm">
                    @svg('heroicon-o-check-circle', 'w-3 h-3')
                    Gewonnen
                </x-ui-badge>
            @endif
        </x-ui-page-navbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Deal-Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-4 space-y-6">
                {{-- Quick Stats --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Schnellübersicht</h3>
                    <div class="space-y-3">
                        <div class="p-3 bg-[var(--ui-primary-5)] border border-[var(--ui-primary)]/30 rounded-lg">
                            <div class="text-xs text-[var(--ui-primary)] font-medium">Deal Wert</div>
                            <div class="text-lg font-bold text-[var(--ui-primary)]">
                                {{ $deal->deal_value ? number_format((float) $deal->deal_value, 2, ',', '.') . ' €' : 'Nicht festgelegt' }}
                            </div>
                        </div>
                        <div class="p-3 bg-[var(--ui-success-5)] border border-[var(--ui-success)]/30 rounded-lg">
                            <div class="text-xs text-[var(--ui-success)] font-medium">Wahrscheinlichkeit</div>
                            <div class="text-lg font-bold text-[var(--ui-success)]">
                                {{ $deal->calculated_probability ? $deal->calculated_probability . '%' : 'Nicht festgelegt' }}
                            </div>
                        </div>
                        @if($deal->hasBillables())
                            <div class="p-3 bg-[var(--ui-primary-5)] border border-[var(--ui-primary)]/30 rounded-lg">
                                <div class="text-xs text-[var(--ui-primary)] font-medium">Billables</div>
                                <div class="text-lg font-bold text-[var(--ui-primary)]">
                                    {{ $deal->billables->count() }} Komponente(n)
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Navigation Buttons --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Navigation</h3>
                    <div class="flex flex-col gap-2">
                    @if($deal->salesBoard)
                        @can('view', $deal->salesBoard)
                                <x-ui-button 
                                    variant="secondary-outline" 
                                    size="sm" 
                                    :href="route('sales.boards.show', $deal->salesBoard)" 
                                    wire:navigate
                                    class="w-full"
                                >
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-arrow-left', 'w-4 h-4')
                                        <span>Board: {{ $deal->salesBoard?->name }}</span>
                                    </span>
                                </x-ui-button>
                        @else
                                <x-ui-button 
                                    variant="secondary-outline" 
                                    size="sm" 
                                    disabled="true"
                                    title="Kein Zugriff auf das Board"
                                    class="w-full"
                                >
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-arrow-left', 'w-4 h-4')
                                        <span>Board: {{ $deal->salesBoard?->name }}</span>
                                    </span>
                                </x-ui-button>
                            @endcan
                        @endif
                        <x-ui-button 
                            variant="secondary-outline" 
                            size="sm" 
                            :href="route('sales.my-deals')" 
                            wire:navigate
                            class="w-full"
                        >
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-arrow-left', 'w-4 h-4')
                                <span>Meine Deals</span>
                            </span>
                        </x-ui-button>
                    </div>
                </div>

                {{-- Deal Status --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Deal Status</h3>
                    @can('update', $deal)
                        <div class="p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <x-ui-input-checkbox
                                model="deal.is_done"
                                checked-label="Deal gewonnen"
                                unchecked-label="Als gewonnen markieren"
                                size="md"
                                block="true"
                                variant="success"
                                :icon="@svg('heroicon-o-check-circle', 'w-4 h-4')->toHtml()"
                            />
                        </div>
                    @else
                        <div class="p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <x-ui-badge variant="{{ $deal->is_done ? 'success' : 'neutral' }}" size="lg" class="w-full justify-center">
                                @svg('heroicon-o-check-circle', 'w-4 h-4')
                                {{ $deal->is_done ? 'Deal gewonnen' : 'Deal offen' }}
                            </x-ui-badge>
                        </div>
                    @endcan
                    
                    @if($deal->is_done)
                        <div class="mt-3 p-3 bg-[var(--ui-success-5)] border border-[var(--ui-success)]/30 rounded-lg">
                            <div class="flex items-center gap-2">
                                @svg('heroicon-o-check-circle', 'w-5 h-5 text-[var(--ui-success)]')
                                <span class="text-sm font-medium text-[var(--ui-success)]">Deal erfolgreich abgeschlossen</span>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Deal Info --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Deal Info</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded">
                            <span class="text-[var(--ui-muted)]">Erstellt:</span>
                            <span class="font-medium text-[var(--ui-secondary)]">{{ $deal->created_at->format('d.m.Y H:i') }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded">
                            <span class="text-[var(--ui-muted)]">Geändert:</span>
                            <span class="font-medium text-[var(--ui-secondary)]">{{ $deal->updated_at->format('d.m.Y H:i') }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded">
                            <span class="text-[var(--ui-muted)]">Erstellt von:</span>
                            <span class="font-medium text-[var(--ui-secondary)]">{{ $deal->user?->name ?? 'Unbekannt' }}</span>
                        </div>
                        @if($deal->userInCharge)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded">
                                <span class="text-[var(--ui-muted)]">Zugewiesen an:</span>
                                <span class="font-medium text-[var(--ui-secondary)]">{{ $deal->userInCharge->name }}</span>
                            </div>
                        @endif
                        <div class="p-2 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded">
                            <div class="text-[var(--ui-muted)] mb-1 text-xs">Deal ID:</div>
                            <div class="font-mono text-xs text-[var(--ui-secondary)] break-all">{{ $deal->uuid }}</div>
                </div>
            </div>
        </div>

                {{-- Löschen-Buttons --}}
                @can('delete', $deal)
                    <div>
                        <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Aktionen</h3>
                        <div class="flex flex-col gap-2">
                            <x-ui-confirm-button 
                                action="deleteDealAndReturnToDashboard" 
                                text="Zu Meinen Deals" 
                                confirmText="Löschen?" 
                                variant="danger-outline"
                                :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()"
                            />
                            
                            @if($deal->salesBoard)
                                <x-ui-confirm-button 
                                    action="deleteDealAndReturnToBoard" 
                                    text="Zum Board" 
                                    confirmText="Löschen?" 
                                    variant="danger-outline"
                                    :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()"
                                />
                            @endif
                        </div>
                    </div>
                @endcan
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="true" storeKey="activityOpen" side="right">
            <div class="p-4 h-full overflow-y-auto">
                <livewire:activity-log.index
                    :model="$deal"
                    :key="get_class($deal) . '_' . $deal->id"
                />
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
            {{-- Deal Form --}}
            <div class="mb-6">
            <h3 class="text-lg font-semibold mb-4 text-[var(--ui-secondary)] flex items-center gap-2">
                    @svg('heroicon-o-pencil-square', 'w-5 h-5')
                    Deal bearbeiten
                </h3>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Linke Spalte: Titel & Beschreibung --}}
                    <div class="space-y-4">
                        @can('update', $deal)
                            <x-ui-input-text 
                                name="deal.title"
                                label="Deal-Titel"
                                wire:model.live.debounce.500ms="deal.title"
                                placeholder="Deal-Titel eingeben..."
                                required
                                :errorKey="'deal.title'"
                            />
                        @else
                            <div>
                            <label class="font-semibold text-[var(--ui-secondary)]">Deal-Titel:</label>
                            <div class="p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">{{ $deal->title }}</div>
                            </div>
                        @endcan

                        @can('update', $deal)
                            <x-ui-input-textarea 
                                name="deal.description"
                                label="Deal Beschreibung"
                                wire:model.live.debounce.500ms="deal.description"
                                placeholder="Deal Beschreibung eingeben..."
                                rows="6"
                                :errorKey="'deal.description'"
                            />
                        @else
                            <div>
                            <label class="font-semibold text-[var(--ui-secondary)]">Beschreibung:</label>
                            <div class="p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 whitespace-pre-wrap">{{ $deal->description ?: 'Keine Beschreibung vorhanden' }}</div>
                            </div>
                        @endcan
                    </div>

                    {{-- Rechte Spalte: Metadaten --}}
                    <div class="space-y-4">
                            {{-- Deal Source & Type --}}
                            <div class="grid grid-cols-2 gap-4">
                                @can('update', $deal)
                                    <x-ui-input-select
                                        name="deal.sales_deal_source_id"
                                        label="Deal Quelle"
                                        :options="$dealSources"
                                        optionValue="id"
                                        optionLabel="label"
                                        :nullable="true"
                                        nullLabel="– Quelle auswählen –"
                                        wire:model.live="deal.sales_deal_source_id"
                                        :errorKey="'deal.sales_deal_source_id'"
                                    />
                                @else
                                    <div>
                                <label class="font-semibold text-[var(--ui-secondary)]">Deal Quelle:</label>
                                <div class="p-2 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">{{ $deal->dealSource?->label ?: '–' }}</div>
                                    </div>
                                @endcan

                                @can('update', $deal)
                                    <x-ui-input-select
                                        name="deal.sales_deal_type_id"
                                        label="Deal Typ"
                                        :options="$dealTypes"
                                        optionValue="id"
                                        optionLabel="label"
                                        :nullable="true"
                                        nullLabel="– Typ auswählen –"
                                        wire:model.live="deal.sales_deal_type_id"
                                        :errorKey="'deal.sales_deal_type_id'"
                                    />
                                @else
                                    <div>
                                <label class="font-semibold text-[var(--ui-secondary)]">Deal Typ:</label>
                                <div class="p-2 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">{{ $deal->dealType?->label ?: '–' }}</div>
                                    </div>
                                @endcan
                            </div>

                    {{-- Billables Management --}}
                    <div class="grid grid-cols-1 gap-4">
                        <x-ui-panel 
                            title="Billables verwalten" 
                            subtitle="Teile deinen Deal in einzelne Komponenten auf"
                            variant="success"
                        >
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    @if($deal->hasBillables())
                                            <x-ui-badge variant="success" size="sm">
                                                {{ $deal->billables->count() }} Komponente(n)
                                        </x-ui-badge>
                                    @endif
                                </div>
                            <x-ui-button 
                                variant="success" 
                                        size="md" 
                                wire:click="openBillablesModal"
                                        class="flex items-center gap-2">
                                        @svg('heroicon-o-calculator', 'w-4 h-4')
                                        {{ $deal->hasBillables() ? 'Bearbeiten' : 'Hinzufügen' }}
                            </x-ui-button>
                                </div>
                            
                            @if($deal->hasBillables())
                                    <div class="grid grid-cols-2 gap-3 pt-3 border-t border-[var(--ui-border)]/40">
                                        <div class="p-3 bg-[var(--ui-success-5)] rounded-lg border border-[var(--ui-success)]/30">
                                            <div class="text-xs text-[var(--ui-success)] font-medium mb-1">Gesamtwert</div>
                                            <div class="text-lg font-bold text-[var(--ui-success)]">
                                            {{ number_format((float) $deal->deal_value, 2, ',', '.') }} €
                                        </div>
                                    </div>
                                        <div class="p-3 bg-[var(--ui-primary-5)] rounded-lg border border-[var(--ui-primary)]/30">
                                            <div class="text-xs text-[var(--ui-primary)] font-medium mb-1">Erwarteter Wert</div>
                                            <div class="text-lg font-bold text-[var(--ui-primary)]">
                                            {{ number_format((float) $deal->billables_expected_total, 2, ',', '.') }} €
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="text-center py-4 text-sm text-[var(--ui-muted)]">
                                        Noch keine Billables vorhanden
                                </div>
                            @endif
                        </div>
                        </x-ui-panel>
                    </div>

                    {{-- CRM Integration --}}
                    <div class="grid grid-cols-1 gap-4">
                        <div class="p-4 bg-gradient-to-r from-[var(--ui-primary-5)] to-[var(--ui-primary-10)] border border-[var(--ui-primary)]/30 rounded-lg">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h4 class="text-lg font-semibold text-[var(--ui-primary)] mb-1">CRM Verknüpfung</h4>
                                    <p class="text-sm text-[var(--ui-primary)]/80">Verknüpfe mit Companies und Contacts</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($deal->companies()->count() > 0 || $deal->contacts()->count() > 0)
                                        <x-ui-badge variant="primary" size="sm">
                                            {{ $deal->companies()->count() + $deal->contacts()->count() }} Verknüpfung(en)
                                        </x-ui-badge>
                                    @endif
                                </div>
                            </div>
                            
                            <x-ui-button 
                                variant="primary" 
                                size="lg" 
                                wire:click="$dispatch('open-modal-customer-deal', { dealId: {{ $deal->id }} })"
                                class="w-full flex items-center justify-center gap-2">
                                @svg('heroicon-o-link', 'w-5 h-5')
                                CRM verknüpfen
                            </x-ui-button>
                            
                            @if($deal->companies()->count() > 0 || $deal->contacts()->count() > 0)
                                <div class="mt-3 space-y-2">
                                    @foreach($deal->companies() as $company)
                                        <div class="p-2 bg-white rounded border border-[var(--ui-primary)]/20 flex items-center gap-2">
                                            @svg('heroicon-o-building-office', 'w-4 h-4 text-[var(--ui-primary)]')
                                            <span class="text-sm font-medium text-[var(--ui-primary)]">{{ $company->name }}</span>
                                            <x-ui-badge variant="primary" size="xs">Company</x-ui-badge>
                                        </div>
                                    @endforeach
                                    @foreach($deal->contacts() as $contact)
                                        <div class="p-2 bg-white rounded border border-[var(--ui-primary)]/20 flex items-center gap-2">
                                            @svg('heroicon-o-user', 'w-4 h-4 text-[var(--ui-primary)]')
                                            <span class="text-sm font-medium text-[var(--ui-primary)]">{{ $contact->display_name }}</span>
                                            <x-ui-badge variant="primary" size="xs">Contact</x-ui-badge>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                        {{-- Fälligkeitsdatum & Zugewiesener Benutzer --}}
                        <div class="grid grid-cols-2 gap-4">
                            @can('update', $deal)
                                <x-ui-input-date
                                    name="deal.due_date"
                                    label="Fälligkeitsdatum"
                                    wire:model.live="deal.due_date"
                                    :nullable="true"
                                    :errorKey="'deal.due_date'"
                                />
                            @else
                                <div>
                                <label class="font-semibold text-[var(--ui-secondary)]">Fälligkeitsdatum:</label>
                                <div class="p-2 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                                        {{ $deal->due_date ? $deal->due_date->format('d.m.Y') : '–' }}
                                    </div>
                                </div>
                            @endcan

                            @can('update', $deal)
                                <x-ui-input-select
                                    name="deal.user_in_charge_id"
                                    label="Zugewiesen an"
                                    :options="$teamUsers"
                                    optionValue="id"
                                    optionLabel="name"
                                    :nullable="true"
                                    nullLabel="– Niemand zugewiesen –"
                                    wire:model.live="deal.user_in_charge_id"
                                />
                            @else
                                <div>
                                <label class="font-semibold text-[var(--ui-secondary)]">Zugewiesen an:</label>
                                <div class="p-2 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                                        {{ $deal->userInCharge?->name ?? 'Niemand zugewiesen' }}
                                    </div>
                                </div>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
    </x-ui-page-container>

    <!-- Billables Modal -->
    <livewire:sales.billables-modal />
    
    <!-- Customer Deal Settings Modal -->
    <livewire:sales.customer-deal-settings-modal />
</x-ui-page>

@push('scripts')
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('openBillablesModal', (dealId) => {
            @this.call('openBillablesModal');
        });
    });
</script>
@endpush
