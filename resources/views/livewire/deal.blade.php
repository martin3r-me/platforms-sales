<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Sales', 'href' => route('sales.dashboard'), 'icon' => 'currency-euro'],
            $deal->salesBoard
                ? ['label' => $deal->salesBoard->name, 'href' => route('sales.boards.show', $deal->salesBoard)]
                : ['label' => 'Meine Deals', 'href' => route('sales.my-deals')],
            ['label' => $deal->title],
        ]">
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-4 space-y-5 bg-[var(--nx-bg)]">
                {{-- Navigation --}}
                <div class="flex flex-col gap-2">
                    @if($deal->salesBoard)
                        @can('view', $deal->salesBoard)
                            <x-nx-button variant="secondary" :href="route('sales.boards.show', $deal->salesBoard)" wire:navigate class="w-full">
                                <span class="flex items-center gap-2">
                                    @svg('heroicon-o-rectangle-stack', 'w-4 h-4')
                                    Board: {{ $deal->salesBoard->name }}
                                </span>
                            </x-nx-button>
                        @endcan
                    @endif
                    <x-nx-button variant="secondary" :href="route('sales.my-deals')" wire:navigate class="w-full">
                        <span class="flex items-center gap-2">
                            @svg('heroicon-o-clipboard-document-list', 'w-4 h-4')
                            Meine Deals
                        </span>
                    </x-nx-button>
                </div>

                {{-- Deal Status --}}
                <section class="p-3 rounded-lg bg-[color:var(--nx-surface)] border border-[color:var(--nx-line)] shadow-[var(--nx-shadow-card)] space-y-2">
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--nx-muted)] mb-1">Deal Status</h3>
                    @can('update', $deal)
                        <x-nx-input-checkbox label="Deal gewonnen" wire:model="deal.is_done" />
                    @else
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                @svg('heroicon-o-check-circle', 'w-4 h-4 text-[color:var(--nx-success)]')
                                <span class="text-sm text-[color:var(--nx-text)]">Deal Status</span>
                            </div>
                            <span class="text-sm font-semibold {{ $deal->is_done ? 'text-[color:var(--nx-success)]' : 'text-[color:var(--nx-text)]' }}">
                                {{ $deal->is_done ? 'Gewonnen' : 'Offen' }}
                            </span>
                        </div>
                    @endcan

                    @if($deal->is_done)
                        <x-nx-callout variant="success" title="Deal erfolgreich abgeschlossen" />
                    @endif
                </section>

                {{-- Schnellübersicht --}}
                <section class="p-3 rounded-lg bg-[color:var(--nx-surface)] border border-[color:var(--nx-line)] shadow-[var(--nx-shadow-card)]">
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--nx-muted)] mb-2">Schnellübersicht</h3>
                    <dl class="space-y-2 text-[11px] m-0">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="inline-flex items-center gap-1.5 text-[var(--nx-muted)]">
                                @svg('heroicon-o-currency-euro', 'w-3.5 h-3.5 text-[color:var(--nx-success)]')
                                Deal Wert
                            </dt>
                            <dd class="m-0 tabular-nums font-semibold text-[color:var(--nx-success)]">
                                {{ $deal->deal_value ? number_format((float) $deal->deal_value, 0, ',', '.') . ' €' : '–' }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="inline-flex items-center gap-1.5 text-[var(--nx-muted)]">
                                @svg('heroicon-o-chart-bar', 'w-3.5 h-3.5 text-[color:var(--nx-info)]')
                                Wahrscheinlichkeit
                            </dt>
                            <dd class="m-0 tabular-nums font-semibold text-[color:var(--nx-info)]">
                                {{ $deal->calculated_probability ? $deal->calculated_probability . '%' : '–' }}
                            </dd>
                        </div>
                        @if($deal->hasBillables())
                            <div class="flex items-center justify-between gap-3">
                                <dt class="inline-flex items-center gap-1.5 text-[var(--nx-muted)]">
                                    @svg('heroicon-o-calculator', 'w-3.5 h-3.5 text-[color:var(--nx-info)]')
                                    Billables
                                </dt>
                                <dd class="m-0 tabular-nums font-semibold text-[color:var(--nx-info)]">
                                    {{ $deal->billables->count() }}
                                </dd>
                            </div>
                        @endif
                    </dl>
                </section>

                {{-- Aktionen --}}
                @can('delete', $deal)
                    <section class="p-3 rounded-lg bg-[color:var(--nx-surface)] border border-[color:var(--nx-line)] shadow-[var(--nx-shadow-card)]">
                        <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--nx-muted)] mb-2">Aktionen</h3>
                        <div class="space-y-2">
                            <x-nx-button variant="danger" wire:click="deleteDealAndReturnToDashboard" wire:confirm="Wirklich löschen?" class="w-full">
                                <span class="flex items-center gap-2">
                                    @svg('heroicon-o-trash', 'w-4 h-4')
                                    Löschen & zu Meine Deals
                                </span>
                            </x-nx-button>
                            @if($deal->salesBoard)
                                <x-nx-button variant="danger" wire:click="deleteDealAndReturnToBoard" wire:confirm="Wirklich löschen?" class="w-full">
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-trash', 'w-4 h-4')
                                        Löschen & zum Board
                                    </span>
                                </x-nx-button>
                            @endif
                        </div>
                    </section>
                @endcan
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="true" storeKey="activityOpen" side="right">
            <div class="p-4 h-full overflow-y-auto bg-[var(--nx-bg)]">
                <livewire:activity-log.index
                    :model="$deal"
                    :key="get_class($deal) . '_' . $deal->id"
                />
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container spacing="space-y-6">
        {{-- Header Card --}}
        <x-nx-card>
            <div class="p-2 lg:p-4">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div class="flex-1 min-w-0">
                        <h1 class="text-3xl font-bold text-[color:var(--nx-text)] mb-4 tracking-tight leading-tight">{{ $deal->title }}</h1>

                        {{-- Prominente Metriken: Wert, Wahrscheinlichkeit, Fällig --}}
                        <div class="flex flex-wrap items-center gap-6 text-sm">
                            @if($deal->deal_value)
                                <span class="flex items-center gap-2">
                                    @svg('heroicon-o-currency-euro', 'w-4 h-4 text-[color:var(--nx-success)]')
                                    <span class="text-[color:var(--nx-muted)]">Deal Wert:</span>
                                    <span class="text-[color:var(--nx-success)] font-semibold">{{ number_format((float) $deal->deal_value, 0, ',', '.') }} €</span>
                                </span>
                            @endif
                            @if($deal->probability_percent)
                                <span class="flex items-center gap-2">
                                    @svg('heroicon-o-chart-bar', 'w-4 h-4 text-[color:var(--nx-info)]')
                                    <span class="text-[color:var(--nx-muted)]">Wahrscheinlichkeit:</span>
                                    <span class="text-[color:var(--nx-text)] font-semibold">{{ $deal->probability_percent }}%</span>
                                </span>
                            @endif
                            @if($deal->due_date)
                                @php
                                    $isOverdue = $deal->due_date->isPast() && !$deal->is_done;
                                    $isToday = $deal->due_date->isToday();
                                    $dueDateColor = $isOverdue ? 'text-[color:var(--nx-danger)]' : ($isToday ? 'text-[color:var(--nx-warning)]' : 'text-[color:var(--nx-muted)]');
                                    $dueDateTextColor = $isOverdue ? 'text-[color:var(--nx-danger)]' : ($isToday ? 'text-[color:var(--nx-warning)]' : 'text-[color:var(--nx-text)]');
                                @endphp
                                <span class="flex items-center gap-2">
                                    @svg('heroicon-o-calendar', 'w-4 h-4 ' . $dueDateColor)
                                    <span class="text-[color:var(--nx-muted)]">Fällig:</span>
                                    <span class="{{ $dueDateTextColor }} font-semibold">{{ $deal->due_date->format('d.m.Y') }}</span>
                                </span>
                            @endif
                        </div>

                        {{-- Aufklappbare Metadaten --}}
                        @if($deal->salesBoard || $deal->salesBoardSlot || $deal->dealSource || $deal->dealType || $deal->user || $deal->userInCharge || $deal->hasBillables() || $deal->companies()->count() > 0 || $deal->contacts()->count() > 0)
                            <details class="mt-4">
                                <summary class="text-sm text-[color:var(--nx-muted)] cursor-pointer hover:text-[color:var(--nx-text)] transition-colors select-none">
                                    Weitere Details anzeigen
                                </summary>
                                <div class="mt-3 space-y-2">
                                    {{-- Board & Klassifizierung --}}
                                    <div class="flex flex-wrap items-center gap-6 text-sm text-[color:var(--nx-muted)]">
                                        @if($deal->salesBoard)
                                            <span class="flex items-center gap-2">
                                                @svg('heroicon-o-rectangle-stack', 'w-4 h-4')
                                                <span>Board: <span class="text-[color:var(--nx-text)]">{{ $deal->salesBoard->name }}</span></span>
                                            </span>
                                        @endif
                                        @if($deal->salesBoardSlot)
                                            <span class="flex items-center gap-2">
                                                @svg('heroicon-o-view-columns', 'w-4 h-4')
                                                <span>Spalte: <span class="text-[color:var(--nx-text)]">{{ $deal->salesBoardSlot->name }}</span></span>
                                            </span>
                                        @endif
                                        @if($deal->dealSource)
                                            <span class="flex items-center gap-2">
                                                @svg('heroicon-o-signal', 'w-4 h-4')
                                                <span>Quelle: <span class="text-[color:var(--nx-text)]">{{ $deal->dealSource->label }}</span></span>
                                            </span>
                                        @endif
                                        @if($deal->dealType)
                                            <span class="flex items-center gap-2">
                                                @svg('heroicon-o-tag', 'w-4 h-4')
                                                <span>Typ: <span class="text-[color:var(--nx-text)]">{{ $deal->dealType->label }}</span></span>
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Personen --}}
                                    <div class="flex flex-wrap items-center gap-6 text-sm text-[color:var(--nx-muted)]">
                                        @if($deal->user)
                                            <span class="flex items-center gap-2">
                                                @svg('heroicon-o-user-circle', 'w-4 h-4')
                                                <span>Erstellt von: <span class="text-[color:var(--nx-text)]">{{ $deal->user->name }}</span></span>
                                            </span>
                                        @endif
                                        @if($deal->userInCharge)
                                            <span class="flex items-center gap-2">
                                                @svg('heroicon-o-user', 'w-4 h-4')
                                                <span>Verantwortlich: <span class="text-[color:var(--nx-text)]">{{ $deal->userInCharge->name }}</span></span>
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Billables & CRM --}}
                                    <div class="flex flex-wrap items-center gap-6 text-sm text-[color:var(--nx-muted)]">
                                        @if($deal->hasBillables())
                                            <span class="flex items-center gap-2">
                                                @svg('heroicon-o-calculator', 'w-4 h-4')
                                                <span>Billables: <span class="text-[color:var(--nx-text)] font-medium">{{ $deal->billables->count() }} Komponente(n)</span></span>
                                            </span>
                                        @endif
                                        @if($deal->companies()->count() > 0 || $deal->contacts()->count() > 0)
                                            <span class="flex items-center gap-2">
                                                @svg('heroicon-o-link', 'w-4 h-4')
                                                <span>CRM: <span class="text-[color:var(--nx-text)] font-medium">{{ $deal->companies()->count() + $deal->contacts()->count() }} Verknüpfung(en)</span></span>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </details>
                        @endif
                    </div>

                    {{-- Status Badges --}}
                    <div class="flex flex-col items-end gap-2 flex-shrink-0">
                        @if($deal->is_done)
                            <x-nx-badge variant="success">Gewonnen</x-nx-badge>
                        @endif
                        @if($deal->isHot())
                            <x-nx-badge variant="danger">Hot</x-nx-badge>
                        @endif
                        @if($deal->is_starred)
                            <x-nx-badge variant="warning">Favorit</x-nx-badge>
                        @endif
                        @if($deal->isHighValue())
                            <x-nx-badge variant="success">High Value</x-nx-badge>
                        @endif
                    </div>
                </div>
            </div>
        </x-nx-card>

        {{-- Form Card --}}
        <x-nx-card>
            <div class="p-2 lg:p-4 space-y-8">
                {{-- Grundinformationen --}}
                <x-nx-section icon="heroicon-o-information-circle" title="Grundinformationen">
                    <div class="grid grid-cols-2 gap-6">
                        <div class="col-span-2">
                            @can('update', $deal)
                                <x-nx-input-text
                                    name="deal.title"
                                    label="Deal-Titel"
                                    wire:model.live.debounce.500ms="deal.title"
                                    placeholder="Deal-Titel eingeben..."
                                    required
                                    :errorKey="'deal.title'"
                                />
                            @else
                                <div>
                                    <label class="block text-sm font-semibold text-[color:var(--nx-text)] mb-1">Deal-Titel</label>
                                    <div class="p-3 bg-[color:var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)]">{{ $deal->title }}</div>
                                </div>
                            @endcan
                        </div>
                        <div class="col-span-2">
                            @can('update', $deal)
                                <x-nx-input-textarea
                                    name="deal.description"
                                    label="Beschreibung"
                                    wire:model.live.debounce.500ms="deal.description"
                                    placeholder="Deal-Beschreibung (optional)"
                                    rows="4"
                                    :errorKey="'deal.description'"
                                />
                            @else
                                <div>
                                    <label class="block text-sm font-semibold text-[color:var(--nx-text)] mb-1">Beschreibung</label>
                                    <div class="p-3 bg-[color:var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] whitespace-pre-wrap">{{ $deal->description ?: 'Keine Beschreibung' }}</div>
                                </div>
                            @endcan
                        </div>
                        <div>
                            @can('update', $deal)
                                <x-nx-input-select
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
                                    <label class="block text-sm font-semibold text-[color:var(--nx-text)] mb-1">Deal Quelle</label>
                                    <div class="p-2 bg-[color:var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)]">{{ $deal->dealSource?->label ?: '–' }}</div>
                                </div>
                            @endcan
                        </div>
                        <div>
                            @can('update', $deal)
                                <x-nx-input-select
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
                                    <label class="block text-sm font-semibold text-[color:var(--nx-text)] mb-1">Deal Typ</label>
                                    <div class="p-2 bg-[color:var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)]">{{ $deal->dealType?->label ?: '–' }}</div>
                                </div>
                            @endcan
                        </div>
                    </div>
                </x-nx-section>

                {{-- Fälligkeit & Verantwortung --}}
                <x-nx-section icon="heroicon-o-calendar-days" title="Fälligkeit & Verantwortung">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            @can('update', $deal)
                                <x-nx-input-date
                                    name="deal.due_date"
                                    label="Fälligkeitsdatum"
                                    wire:model.live="deal.due_date"
                                    :nullable="true"
                                    :errorKey="'deal.due_date'"
                                />
                            @else
                                <div>
                                    <label class="block text-sm font-semibold text-[color:var(--nx-text)] mb-1">Fälligkeitsdatum</label>
                                    <div class="p-2 bg-[color:var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)]">
                                        {{ $deal->due_date ? $deal->due_date->format('d.m.Y') : '–' }}
                                    </div>
                                </div>
                            @endcan
                        </div>
                        <div>
                            @can('update', $deal)
                                <x-nx-input-select
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
                                    <label class="block text-sm font-semibold text-[color:var(--nx-text)] mb-1">Verantwortlicher</label>
                                    <div class="p-2 bg-[color:var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)]">
                                        {{ $deal->userInCharge?->name ?? '–' }}
                                    </div>
                                </div>
                            @endcan
                        </div>
                    </div>
                </x-nx-section>

                {{-- Billables --}}
                <x-nx-section icon="heroicon-o-calculator" title="Billables" description="Teile deinen Deal in einzelne Komponenten auf">
                    <x-slot name="action">
                        <x-nx-button variant="primary" wire:click="openBillablesModal">
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-calculator', 'w-4 h-4')
                                {{ $deal->hasBillables() ? 'Bearbeiten' : 'Hinzufügen' }}
                            </span>
                        </x-nx-button>
                    </x-slot>

                    @if($deal->hasBillables())
                        @php
                            $oneTimeBillables = $deal->billables->filter(fn($b) => $b->isOneTime());
                            $recurringBillables = $deal->billables->filter(fn($b) => $b->isRecurring());
                            $oneTimeTotal = $oneTimeBillables->sum('total_value');
                            $recurringTotal = $recurringBillables->sum('total_value');
                        @endphp

                        {{-- Summary Stats --}}
                        <x-nx-stat-grid :cols="4" class="mb-4">
                            <x-nx-stat
                                label="Gesamtwert"
                                value="{{ number_format((float) $deal->deal_value, 2, ',', '.') }} €"
                                icon="heroicon-o-currency-euro"
                                accent="var(--nx-success)"
                            />
                            <x-nx-stat
                                label="Einmalig"
                                value="{{ number_format((float) $oneTimeTotal, 2, ',', '.') }} €"
                                hint="{{ $oneTimeBillables->count() }} Position(en)"
                                icon="heroicon-o-banknotes"
                            />
                            <x-nx-stat
                                label="Wiederkehrend"
                                value="{{ number_format((float) $recurringTotal, 2, ',', '.') }} €"
                                hint="{{ $recurringBillables->count() }} Position(en)"
                                icon="heroicon-o-arrow-path"
                                accent="var(--nx-info)"
                            />
                            <x-nx-stat
                                label="Gewichtete Wahrscheinlichkeit"
                                value="{{ $deal->calculated_probability }}%"
                                icon="heroicon-o-chart-bar"
                            />
                        </x-nx-stat-grid>

                        {{-- Einmalige Billables --}}
                        @if($oneTimeBillables->count() > 0)
                            <div class="mb-4">
                                <h3 class="text-sm font-semibold text-[color:var(--nx-text)] mb-2 flex items-center gap-2">
                                    @svg('heroicon-o-banknotes', 'w-4 h-4 text-[color:var(--nx-muted)]')
                                    Einmalig
                                </h3>
                                <x-nx-card flush>
                                    <ul class="divide-y divide-[color:var(--nx-line)] m-0 p-0 list-none">
                                        @foreach($oneTimeBillables as $billable)
                                            <li>
                                                <x-nx-list-item :title="$billable->name" :meta="$billable->formatted_total_value">
                                                    <x-slot name="trailing">
                                                        <x-nx-badge variant="neutral">Einmalig</x-nx-badge>
                                                        @if($billable->start_date)
                                                            <span class="text-xs text-[color:var(--nx-muted)]">{{ $billable->start_date->format('d.m.Y') }}</span>
                                                        @endif
                                                    </x-slot>
                                                </x-nx-list-item>
                                            </li>
                                        @endforeach
                                    </ul>
                                </x-nx-card>
                            </div>
                        @endif

                        {{-- Wiederkehrende Billables --}}
                        @if($recurringBillables->count() > 0)
                            <div>
                                <h3 class="text-sm font-semibold text-[color:var(--nx-text)] mb-2 flex items-center gap-2">
                                    @svg('heroicon-o-arrow-path', 'w-4 h-4 text-[color:var(--nx-info)]')
                                    Wiederkehrend
                                </h3>
                                <x-nx-card flush>
                                    <ul class="divide-y divide-[color:var(--nx-line)] m-0 p-0 list-none">
                                        @foreach($recurringBillables as $billable)
                                            <li class="px-4 py-2.5">
                                                <div class="flex items-center justify-between gap-3">
                                                    <div class="flex items-center gap-3 min-w-0">
                                                        <span class="text-sm font-medium text-[color:var(--nx-text)] truncate">{{ $billable->name }}</span>
                                                        <x-nx-badge variant="success">Wiederkehrend</x-nx-badge>
                                                    </div>
                                                    <span class="text-sm font-semibold text-[color:var(--nx-info)] flex-shrink-0 ml-3">{{ $billable->formatted_total_value }}</span>
                                                </div>
                                                <div class="flex items-center gap-4 mt-1.5 text-xs text-[color:var(--nx-muted)]">
                                                    <span>{{ $billable->formatted_amount }} {{ match($billable->billing_interval) { 'quarterly' => '/ Quartal', 'yearly' => '/ Jahr', default => '/ Monat' } }}</span>
                                                    @if($billable->start_date)
                                                        <span class="flex items-center gap-1">
                                                            @svg('heroicon-o-calendar', 'w-3 h-3')
                                                            {{ $billable->start_date->format('d.m.Y') }}
                                                            @if($billable->end_date)
                                                                – {{ $billable->end_date->format('d.m.Y') }}
                                                            @endif
                                                        </span>
                                                    @endif
                                                    @if($billable->duration_months)
                                                        <span>{{ $billable->duration_months }} Monate</span>
                                                    @endif
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </x-nx-card>
                            </div>
                        @endif
                    @else
                        <x-nx-empty icon="heroicon-o-calculator">
                            Noch keine Billables vorhanden
                            <span class="block mt-1">Teile deinen Deal in einzelne Komponenten auf</span>
                        </x-nx-empty>
                    @endif
                </x-nx-section>

                {{-- CRM Verknüpfung --}}
                <x-nx-section icon="heroicon-o-link" title="CRM Verknüpfung" description="Verknüpfe mit Companies und Contacts">
                    <x-slot name="action">
                        <x-nx-button variant="primary" wire:click="$dispatch('open-modal-customer-deal', { dealId: {{ $deal->id }} })">
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-link', 'w-4 h-4')
                                CRM verknüpfen
                            </span>
                        </x-nx-button>
                    </x-slot>

                    @if($deal->companies()->count() > 0 || $deal->contacts()->count() > 0)
                        <x-nx-card flush>
                            <ul class="divide-y divide-[color:var(--nx-line)] m-0 p-0 list-none">
                                @foreach($deal->companies() as $company)
                                    <li>
                                        <x-nx-list-item icon="heroicon-o-building-office" :title="$company->name">
                                            <x-slot name="trailing">
                                                <x-nx-badge variant="info">Company</x-nx-badge>
                                            </x-slot>
                                        </x-nx-list-item>
                                    </li>
                                @endforeach
                                @foreach($deal->contacts() as $contact)
                                    <li>
                                        <x-nx-list-item icon="heroicon-o-user" :title="$contact->display_name">
                                            <x-slot name="trailing">
                                                <x-nx-badge variant="info">Contact</x-nx-badge>
                                            </x-slot>
                                        </x-nx-list-item>
                                    </li>
                                @endforeach
                            </ul>
                        </x-nx-card>
                    @else
                        <x-nx-empty icon="heroicon-o-link">
                            Noch keine CRM-Verknüpfungen
                            <span class="block mt-1">Verknüpfe diesen Deal mit Companies und Contacts</span>
                        </x-nx-empty>
                    @endif
                </x-nx-section>
            </div>
        </x-nx-card>
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
