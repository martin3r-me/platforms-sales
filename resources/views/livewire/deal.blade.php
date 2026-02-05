<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Navigation --}}
                <div class="flex flex-col gap-2">
                    @if($deal->salesBoard)
                        @can('view', $deal->salesBoard)
                            <x-ui-button variant="secondary-outline" size="sm" :href="route('sales.boards.show', $deal->salesBoard)" wire:navigate class="w-full">
                                <span class="flex items-center gap-2">
                                    @svg('heroicon-o-rectangle-stack', 'w-4 h-4')
                                    Board: {{ $deal->salesBoard->name }}
                                </span>
                            </x-ui-button>
                        @endcan
                    @endif
                    <x-ui-button variant="secondary-outline" size="sm" :href="route('sales.my-deals')" wire:navigate class="w-full">
                        <span class="flex items-center gap-2">
                            @svg('heroicon-o-clipboard-document-list', 'w-4 h-4')
                            Meine Deals
                        </span>
                    </x-ui-button>
                </div>

                {{-- Deal Status --}}
                <div class="space-y-2">
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
                        <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <div class="flex items-center gap-2">
                                @svg('heroicon-o-check-circle', 'w-4 h-4 text-[var(--ui-success)]')
                                <span class="text-sm text-[var(--ui-secondary)]">Deal Status</span>
                            </div>
                            <span class="text-sm font-semibold {{ $deal->is_done ? 'text-[var(--ui-success)]' : 'text-[var(--ui-secondary)]' }}">
                                {{ $deal->is_done ? 'Gewonnen' : 'Offen' }}
                            </span>
                        </div>
                    @endcan

                    @if($deal->is_done)
                        <div class="p-3 bg-[var(--ui-success-5)] border border-[var(--ui-success)]/30 rounded-lg">
                            <div class="flex items-center gap-2">
                                @svg('heroicon-o-check-circle', 'w-5 h-5 text-[var(--ui-success)]')
                                <span class="text-sm font-medium text-[var(--ui-success)]">Deal erfolgreich abgeschlossen</span>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Schnellübersicht --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Schnellübersicht</h3>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <div class="flex items-center gap-2">
                                @svg('heroicon-o-currency-euro', 'w-4 h-4 text-[var(--ui-success)]')
                                <span class="text-sm text-[var(--ui-secondary)]">Deal Wert</span>
                            </div>
                            <span class="text-sm font-semibold text-[var(--ui-success)]">
                                {{ $deal->deal_value ? number_format((float) $deal->deal_value, 0, ',', '.') . ' €' : '–' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <div class="flex items-center gap-2">
                                @svg('heroicon-o-chart-bar', 'w-4 h-4 text-[var(--ui-primary)]')
                                <span class="text-sm text-[var(--ui-secondary)]">Wahrscheinlichkeit</span>
                            </div>
                            <span class="text-sm font-semibold text-[var(--ui-primary)]">
                                {{ $deal->calculated_probability ? $deal->calculated_probability . '%' : '–' }}
                            </span>
                        </div>
                        @if($deal->hasBillables())
                            <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-calculator', 'w-4 h-4 text-[var(--ui-primary)]')
                                    <span class="text-sm text-[var(--ui-secondary)]">Billables</span>
                                </div>
                                <span class="text-sm font-semibold text-[var(--ui-primary)]">
                                    {{ $deal->billables->count() }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Aktionen --}}
                @can('delete', $deal)
                    <div>
                        <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Aktionen</h3>
                        <div class="space-y-2">
                            <x-ui-confirm-button
                                action="deleteDealAndReturnToDashboard"
                                text="Löschen & zu Meine Deals"
                                confirmText="Wirklich löschen?"
                                variant="danger"
                                :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()"
                                class="w-full"
                            />
                            @if($deal->salesBoard)
                                <x-ui-confirm-button
                                    action="deleteDealAndReturnToBoard"
                                    text="Löschen & zum Board"
                                    confirmText="Wirklich löschen?"
                                    variant="danger-outline"
                                    :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()"
                                    class="w-full"
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

    <x-ui-page-container spacing="space-y-6">
        {{-- Header Card --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div class="flex-1 min-w-0">
                        <h1 class="text-3xl font-bold text-[var(--ui-secondary)] mb-4 tracking-tight leading-tight">{{ $deal->title }}</h1>

                        <div class="space-y-2">
                            {{-- Row 1: Board & Klassifizierung --}}
                            <div class="flex flex-wrap items-center gap-6 text-sm text-[var(--ui-muted)]">
                                @if($deal->salesBoard)
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-rectangle-stack', 'w-4 h-4')
                                        <span>Board: <span class="text-[var(--ui-secondary)]">{{ $deal->salesBoard->name }}</span></span>
                                    </span>
                                @endif
                                @if($deal->salesBoardSlot)
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-view-columns', 'w-4 h-4')
                                        <span>Spalte: <span class="text-[var(--ui-secondary)]">{{ $deal->salesBoardSlot->name }}</span></span>
                                    </span>
                                @endif
                                @if($deal->dealSource)
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-signal', 'w-4 h-4')
                                        <span>Quelle: <span class="text-[var(--ui-secondary)]">{{ $deal->dealSource->label }}</span></span>
                                    </span>
                                @endif
                                @if($deal->dealType)
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-tag', 'w-4 h-4')
                                        <span>Typ: <span class="text-[var(--ui-secondary)]">{{ $deal->dealType->label }}</span></span>
                                    </span>
                                @endif
                            </div>

                            {{-- Row 2: Personen & Datum --}}
                            <div class="flex flex-wrap items-center gap-6 text-sm text-[var(--ui-muted)]">
                                @if($deal->user)
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-user-circle', 'w-4 h-4')
                                        <span>Erstellt von: <span class="text-[var(--ui-secondary)]">{{ $deal->user->name }}</span></span>
                                    </span>
                                @endif
                                @if($deal->userInCharge)
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-user', 'w-4 h-4')
                                        <span>Verantwortlich: <span class="text-[var(--ui-secondary)]">{{ $deal->userInCharge->name }}</span></span>
                                    </span>
                                @endif
                                @if($deal->due_date)
                                    @php
                                        $isOverdue = $deal->due_date->isPast() && !$deal->is_done;
                                        $isToday = $deal->due_date->isToday();
                                        $dueDateColor = $isOverdue ? 'text-[var(--ui-danger)]' : ($isToday ? 'text-[var(--ui-warning)]' : 'text-[var(--ui-muted)]');
                                        $dueDateTextColor = $isOverdue ? 'text-[var(--ui-danger)]' : ($isToday ? 'text-[var(--ui-warning)]' : 'text-[var(--ui-secondary)]');
                                    @endphp
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-calendar', 'w-4 h-4 ' . $dueDateColor)
                                        <span>Fällig: <span class="{{ $dueDateTextColor }}">{{ $deal->due_date->format('d.m.Y') }}</span></span>
                                    </span>
                                @endif
                            </div>

                            {{-- Row 3: Werte --}}
                            <div class="flex flex-wrap items-center gap-6 text-sm text-[var(--ui-muted)]">
                                @if($deal->deal_value)
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-currency-euro', 'w-4 h-4 text-[var(--ui-success)]')
                                        <span>Deal Wert: <span class="text-[var(--ui-success)] font-medium">{{ number_format((float) $deal->deal_value, 0, ',', '.') }} €</span></span>
                                    </span>
                                @endif
                                @if($deal->probability_percent)
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-chart-bar', 'w-4 h-4')
                                        <span>Wahrscheinlichkeit: <span class="text-[var(--ui-secondary)] font-medium">{{ $deal->probability_percent }}%</span></span>
                                    </span>
                                @endif
                                @if($deal->hasBillables())
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-calculator', 'w-4 h-4')
                                        <span>Billables: <span class="text-[var(--ui-secondary)] font-medium">{{ $deal->billables->count() }} Komponente(n)</span></span>
                                    </span>
                                @endif
                                @if($deal->companies()->count() > 0 || $deal->contacts()->count() > 0)
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-link', 'w-4 h-4')
                                        <span>CRM: <span class="text-[var(--ui-secondary)] font-medium">{{ $deal->companies()->count() + $deal->contacts()->count() }} Verknüpfung(en)</span></span>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Status Badges --}}
                    <div class="flex flex-col items-end gap-2 flex-shrink-0">
                        @if($deal->is_done)
                            <x-ui-badge variant="success" size="sm">Gewonnen</x-ui-badge>
                        @endif
                        @if($deal->isHot())
                            <x-ui-badge variant="danger" size="sm">Hot</x-ui-badge>
                        @endif
                        @if($deal->is_starred)
                            <x-ui-badge variant="warning" size="sm">Favorit</x-ui-badge>
                        @endif
                        @if($deal->isHighValue())
                            <x-ui-badge variant="success" size="sm">High Value</x-ui-badge>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Form Card --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                {{-- Grundinformationen --}}
                <div class="mb-8 pb-8 border-b border-[var(--ui-border)]/60">
                    <h2 class="text-lg font-semibold text-[var(--ui-secondary)] mb-4">Grundinformationen</h2>
                    <x-ui-form-grid :cols="2" :gap="6">
                        <div class="col-span-2">
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
                                    <label class="block text-sm font-semibold text-[var(--ui-secondary)] mb-1">Deal-Titel</label>
                                    <div class="p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">{{ $deal->title }}</div>
                                </div>
                            @endcan
                        </div>
                        <div class="col-span-2">
                            @can('update', $deal)
                                <x-ui-input-textarea
                                    name="deal.description"
                                    label="Beschreibung"
                                    wire:model.live.debounce.500ms="deal.description"
                                    placeholder="Deal-Beschreibung (optional)"
                                    rows="4"
                                    :errorKey="'deal.description'"
                                />
                            @else
                                <div>
                                    <label class="block text-sm font-semibold text-[var(--ui-secondary)] mb-1">Beschreibung</label>
                                    <div class="p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 whitespace-pre-wrap">{{ $deal->description ?: 'Keine Beschreibung' }}</div>
                                </div>
                            @endcan
                        </div>
                        <div>
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
                                    <label class="block text-sm font-semibold text-[var(--ui-secondary)] mb-1">Deal Quelle</label>
                                    <div class="p-2 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">{{ $deal->dealSource?->label ?: '–' }}</div>
                                </div>
                            @endcan
                        </div>
                        <div>
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
                                    <label class="block text-sm font-semibold text-[var(--ui-secondary)] mb-1">Deal Typ</label>
                                    <div class="p-2 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">{{ $deal->dealType?->label ?: '–' }}</div>
                                </div>
                            @endcan
                        </div>
                    </x-ui-form-grid>
                </div>

                {{-- Fälligkeit & Verantwortung --}}
                <div class="mb-8 pb-8 border-b border-[var(--ui-border)]/60">
                    <h2 class="text-lg font-semibold text-[var(--ui-secondary)] mb-4">Fälligkeit & Verantwortung</h2>
                    <x-ui-form-grid :cols="2" :gap="6">
                        <div>
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
                                    <label class="block text-sm font-semibold text-[var(--ui-secondary)] mb-1">Fälligkeitsdatum</label>
                                    <div class="p-2 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                                        {{ $deal->due_date ? $deal->due_date->format('d.m.Y') : '–' }}
                                    </div>
                                </div>
                            @endcan
                        </div>
                        <div>
                            @can('update', $deal)
                                <x-ui-input-select
                                    name="deal.user_in_charge_id"
                                    label="Verantwortlicher"
                                    :options="$teamUsers"
                                    optionValue="id"
                                    optionLabel="name"
                                    :nullable="true"
                                    nullLabel="– Verantwortlichen auswählen –"
                                    wire:model.live="deal.user_in_charge_id"
                                />
                            @else
                                <div>
                                    <label class="block text-sm font-semibold text-[var(--ui-secondary)] mb-1">Verantwortlicher</label>
                                    <div class="p-2 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                                        {{ $deal->userInCharge?->name ?? '–' }}
                                    </div>
                                </div>
                            @endcan
                        </div>
                    </x-ui-form-grid>
                </div>

                {{-- Billables --}}
                <div class="mb-8 pb-8 border-b border-[var(--ui-border)]/60">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-lg font-semibold text-[var(--ui-secondary)]">Billables</h2>
                            <p class="text-xs text-[var(--ui-muted)] mt-1">Teile deinen Deal in einzelne Komponenten auf</p>
                        </div>
                        <x-ui-button
                            variant="primary"
                            size="sm"
                            wire:click="openBillablesModal"
                            class="flex items-center gap-2"
                        >
                            @svg('heroicon-o-calculator', 'w-4 h-4')
                            {{ $deal->hasBillables() ? 'Bearbeiten' : 'Hinzufügen' }}
                        </x-ui-button>
                    </div>

                    @if($deal->hasBillables())
                        {{-- Summary Stats --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div class="p-4 bg-[var(--ui-success-5)] rounded-lg border border-[var(--ui-success)]/30">
                                <div class="text-xs text-[var(--ui-success)] font-medium mb-1">Gesamtwert</div>
                                <div class="text-xl font-bold text-[var(--ui-success)]">
                                    {{ number_format((float) $deal->deal_value, 2, ',', '.') }} €
                                </div>
                            </div>
                            <div class="p-4 bg-[var(--ui-primary-5)] rounded-lg border border-[var(--ui-primary)]/30">
                                <div class="text-xs text-[var(--ui-primary)] font-medium mb-1">Erwarteter Wert</div>
                                <div class="text-xl font-bold text-[var(--ui-primary)]">
                                    {{ number_format((float) $deal->billables_expected_total, 2, ',', '.') }} €
                                </div>
                            </div>
                            <div class="p-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                                <div class="text-xs text-[var(--ui-muted)] font-medium mb-1">Gewichtete Wahrscheinlichkeit</div>
                                <div class="text-xl font-bold text-[var(--ui-secondary)]">
                                    {{ $deal->calculated_probability }}%
                                </div>
                            </div>
                        </div>

                        {{-- Billable List --}}
                        <div class="space-y-2">
                            @foreach($deal->billables as $billable)
                                <div class="flex items-center justify-between py-2.5 px-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="text-sm font-medium text-[var(--ui-secondary)] truncate">{{ $billable->name }}</span>
                                        @if($billable->isRecurring())
                                            <x-ui-badge variant="success" size="xs">Wiederkehrend</x-ui-badge>
                                        @else
                                            <x-ui-badge variant="neutral" size="xs">Einmalig</x-ui-badge>
                                        @endif
                                    </div>
                                    <span class="text-sm text-[var(--ui-muted)] flex-shrink-0 ml-3">{{ $billable->billing_description }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-[var(--ui-muted)]">
                            <div class="flex justify-center mb-2">
                                @svg('heroicon-o-calculator', 'w-8 h-8')
                            </div>
                            <p class="text-sm">Noch keine Billables vorhanden</p>
                            <p class="text-xs mt-1">Teile deinen Deal in einzelne Komponenten auf</p>
                        </div>
                    @endif
                </div>

                {{-- CRM Verknüpfung --}}
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-lg font-semibold text-[var(--ui-secondary)]">CRM Verknüpfung</h2>
                            <p class="text-xs text-[var(--ui-muted)] mt-1">Verknüpfe mit Companies und Contacts</p>
                        </div>
                        <x-ui-button
                            variant="primary"
                            size="sm"
                            wire:click="$dispatch('open-modal-customer-deal', { dealId: {{ $deal->id }} })"
                            class="flex items-center gap-2"
                        >
                            @svg('heroicon-o-link', 'w-4 h-4')
                            CRM verknüpfen
                        </x-ui-button>
                    </div>

                    @if($deal->companies()->count() > 0 || $deal->contacts()->count() > 0)
                        <div class="space-y-2">
                            @foreach($deal->companies() as $company)
                                <div class="flex items-center gap-3 py-2.5 px-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                                    @svg('heroicon-o-building-office', 'w-4 h-4 text-[var(--ui-primary)]')
                                    <span class="text-sm font-medium text-[var(--ui-secondary)]">{{ $company->name }}</span>
                                    <x-ui-badge variant="primary" size="xs">Company</x-ui-badge>
                                </div>
                            @endforeach
                            @foreach($deal->contacts() as $contact)
                                <div class="flex items-center gap-3 py-2.5 px-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                                    @svg('heroicon-o-user', 'w-4 h-4 text-[var(--ui-primary)]')
                                    <span class="text-sm font-medium text-[var(--ui-secondary)]">{{ $contact->display_name }}</span>
                                    <x-ui-badge variant="primary" size="xs">Contact</x-ui-badge>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-[var(--ui-muted)]">
                            <div class="flex justify-center mb-2">
                                @svg('heroicon-o-link', 'w-8 h-8')
                            </div>
                            <p class="text-sm">Noch keine CRM-Verknüpfungen</p>
                            <p class="text-xs mt-1">Verknüpfe diesen Deal mit Companies und Contacts</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </x-ui-page-container>

    {{-- Modals --}}
    <livewire:sales.billables-modal />
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
