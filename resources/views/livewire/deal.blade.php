<div class="d-flex h-full">
    <!-- Linke Spalte -->
    <div class="flex-grow-1 d-flex flex-col">
        <!-- Header oben (fix) -->
        <div class="border-top-1 border-bottom-1 border-muted border-top-solid border-bottom-solid p-2 flex-shrink-0">
            <div class="d-flex gap-1">
                <div class="d-flex">
                    @if($deal->salesBoard)
                        @can('view', $deal->salesBoard)
                            <a href="{{ route('sales.boards.show', $deal->salesBoard) }}" class="px-3 underline" wire:navigate>
                                Board: {{ $deal->salesBoard?->name }}
                            </a>
                        @else
                            <span class="px-3 text-gray-400" title="Kein Zugriff auf das Board">
                                Board: {{ $deal->salesBoard?->name }} <span class="italic">(kein Zugriff)</span>
                            </span>
                        @endcan
                    @endif

                    <a href="{{ route('sales.my-deals') }}" class="d-flex px-3 border-right-solid border-right-1 border-right-muted underline" wire:navigate>
                        Meine Deals
                    </a>
                </div>
                <div class="flex-grow-1 text-right d-flex items-center justify-end gap-2">
                    <span>{{ $deal->title }}</span>
                    @if($deal->is_done)
                        <x-ui-badge variant="success" size="sm">
                            @svg('heroicon-o-check-circle', 'w-3 h-3')
                            Gewonnen
                        </x-ui-badge>
                    @endif
                </div>
            </div>
        </div>

        <!-- Haupt-Content (nimmt Restplatz, scrollt) -->
        <div class="flex-grow-1 overflow-y-auto p-4">
            
            {{-- Deal Dashboard --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4 text-secondary d-flex items-center gap-2">
                    @svg('heroicon-o-currency-euro', 'w-5 h-5')
                    Deal Übersicht
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    {{-- Deal Wert --}}
                    <div class="p-4 bg-white border rounded-lg shadow-sm">
                        <div class="d-flex items-center gap-2 mb-2">
                            <x-heroicon-o-currency-euro class="w-4 h-4 text-primary"/>
                            <span class="font-medium text-sm">Deal Wert</span>
                        </div>
                        <div class="space-y-1">
                            <div class="text-2xl font-bold text-primary">
                                @if($deal->deal_value)
                                    {{ number_format((float) $deal->deal_value, 0, ',', '.') }} €
                                @else
                                    –
                                @endif
                            </div>
                            @if($deal->deal_value)
                                <div class="text-xs text-gray-500">
                                    Erwarteter Wert: {{ number_format((float) $deal->expected_value, 0, ',', '.') }} €
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Wahrscheinlichkeit --}}
                    <div class="p-4 bg-white border rounded-lg shadow-sm">
                        <div class="d-flex items-center gap-2 mb-2">
                            <x-heroicon-o-chart-pie class="w-4 h-4 text-primary"/>
                            <span class="font-medium text-sm">Wahrscheinlichkeit</span>
                        </div>
                        <div class="space-y-1">
                            <div class="text-2xl font-bold text-primary">
                                @if($deal->probability_percent)
                                    {{ $deal->probability_percent }}%
                                @else
                                    –
                                @endif
                            </div>
                            @if($deal->probability_percent)
                                <div class="text-xs text-gray-500">
                                    @if($deal->probability_percent <= 30)
                                        <span class="text-red-600">Niedrig</span>
                                    @elseif($deal->probability_percent <= 70)
                                        <span class="text-yellow-600">Mittel</span>
                                    @else
                                        <span class="text-green-600">Hoch</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Fälligkeitsdatum --}}
                    <div class="p-4 bg-white border rounded-lg shadow-sm">
                        <div class="d-flex items-center gap-2 mb-2">
                            <x-heroicon-o-calendar class="w-4 h-4 text-primary"/>
                            <span class="font-medium text-sm">Fälligkeitsdatum</span>
                        </div>
                        <div class="space-y-1">
                            <div class="text-2xl font-bold text-primary">
                                @if($deal->due_date)
                                    {{ $deal->due_date->format('d.m.Y') }}
                                @else
                                    –
                                @endif
                            </div>
                            @if($deal->due_date)
                                <div class="text-xs text-gray-500">
                                    @if($deal->due_date->isPast() && !$deal->is_done)
                                        <span class="text-red-600">Überfällig</span>
                                    @elseif($deal->due_date->isToday())
                                        <span class="text-yellow-600">Heute fällig</span>
                                    @else
                                        <span class="text-green-600">{{ $deal->due_date->diffForHumans() }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Deal Details --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4 text-secondary">Deal Details</h3>
                
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
                                <label class="font-semibold">Deal-Titel:</label>
                                <div class="p-3 bg-muted-5 rounded-lg">{{ $deal->title }}</div>
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
                                <label class="font-semibold">Beschreibung:</label>
                                <div class="p-3 bg-muted-5 rounded-lg whitespace-pre-wrap">{{ $deal->description ?: 'Keine Beschreibung vorhanden' }}</div>
                            </div>
                        @endcan
                    </div>

                    {{-- Rechte Spalte: Metadaten --}}
                    <div class="space-y-4">
                            {{-- Deal Wert & Wahrscheinlichkeit --}}
                            <div class="grid grid-cols-2 gap-4">
                                @can('update', $deal)
                                    <div>
                                        <div class="d-flex items-center justify-between mb-2">
                                            <label class="font-semibold">
                                                {{ $deal->hasBillables() ? 'Gesamtwert (aus Billables)' : ($deal->billing_interval === 'one_time' || !$deal->billing_interval ? 'Deal Wert (€)' : 'Gesamtwert über Laufzeit (€)') }}
                                            </label>
                                            <x-ui-button 
                                                variant="secondary-outline" 
                                                size="xs" 
                                                wire:click="openBillablesModal"
                                                class="text-xs">
                                                @svg('heroicon-o-calculator', 'w-4 h-4')
                                                Billables
                                            </x-ui-button>
                                        </div>
                                        
                        @if($deal->hasBillables())
                            <div class="space-y-3">
                                <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="text-sm font-medium text-blue-700">Gesamtwert</div>
                                        <x-ui-badge variant="blue" size="sm">{{ $deal->billables->count() }} Billable(s)</x-ui-badge>
                                    </div>
                                    <div class="text-2xl font-bold text-blue-800">
                                        {{ number_format((float) $deal->deal_value, 2, ',', '.') }} €
                                    </div>
                                </div>
                                <div class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg">
                                    <div class="text-sm font-medium text-green-700 mb-2">Erwarteter Wert</div>
                                    <div class="text-2xl font-bold text-green-800">
                                        {{ number_format((float) $deal->billables_expected_total, 2, ',', '.') }} €
                                    </div>
                                    <div class="text-xs text-green-600 mt-1">
                                        Gewichtete Wahrscheinlichkeit: {{ $deal->calculated_probability }}%
                                    </div>
                                </div>
                            </div>
                                        @else
                                            <x-ui-input-number
                                                name="deal.deal_value"
                                                label=""
                                                wire:model.live.debounce.500ms="deal.deal_value"
                                                placeholder="0.00"
                                                step="0.01"
                                                min="0"
                                                :errorKey="'deal.deal_value'"
                                            />
                                            @if($deal->billing_interval && $deal->billing_interval !== 'one_time' && $deal->monthly_recurring_value && $deal->billing_duration_months)
                                                <div class="d-flex items-center justify-between mt-1">
                                                    <div class="text-xs text-blue-600">
                                                        💡 Automatisch berechnet aus MRR × Laufzeit
                                                    </div>
                                                    <x-ui-button 
                                                        variant="primary-outline" 
                                                        size="xs" 
                                                        wire:click="recalculateDealValue"
                                                        class="text-xs">
                                                        Neu berechnen
                                                    </x-ui-button>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                @else
                                    <div>
                                        <label class="font-semibold">
                                            {{ $deal->hasBillables() ? 'Gesamtwert (aus Billables)' : ($deal->billing_interval === 'one_time' || !$deal->billing_interval ? 'Deal Wert:' : 'Gesamtwert über Laufzeit:') }}
                                        </label>
                                        <div class="p-2 bg-muted-5 rounded-lg">
                                            {{ $deal->deal_value ? number_format((float) $deal->deal_value, 2, ',', '.') . ' €' : '–' }}
                                            @if($deal->hasBillables())
                                                <div class="text-xs text-blue-600 mt-1">
                                                    Gesamtwert: {{ number_format((float) $deal->deal_value, 2, ',', '.') }} €
                                                </div>
                                                <div class="text-xs text-green-600 mt-1">
                                                    Erwarteter Wert: {{ number_format((float) $deal->billables_expected_total, 2, ',', '.') }} €
                                                </div>
                                            @elseif($deal->billing_interval && $deal->billing_interval !== 'one_time' && $deal->monthly_recurring_value && $deal->billing_duration_months)
                                                <div class="text-xs text-blue-600 mt-1">
                                                    (berechnet aus {{ number_format((float) $deal->monthly_recurring_value, 2, ',', '.') }} € × {{ $deal->billing_duration_months }} Monate)
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endcan

                                @can('update', $deal)
                                    <div>
                                        <x-ui-input-number
                                            name="deal.probability_percent"
                                            :label="$deal->hasBillables() ? 'Wahrscheinlichkeit (%) - Gewichtet' : 'Wahrscheinlichkeit (%)'"
                                            wire:model.live.debounce.500ms="deal.probability_percent"
                                            placeholder="0"
                                            min="0"
                                            max="100"
                                            :errorKey="'deal.probability_percent'"
                                        />
                                        @if($deal->hasBillables())
                                            <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                                <div class="flex items-center justify-between mb-2">
                                                    <div class="text-sm font-medium text-blue-700">Automatische Berechnung</div>
                                                    <x-ui-button 
                                                        variant="primary-outline" 
                                                        size="xs" 
                                                        wire:click="recalculateDealProbability"
                                                        class="text-xs">
                                                        @svg('heroicon-o-arrow-path', 'w-3 h-3')
                                                        Neu berechnen
                                                    </x-ui-button>
                                                </div>
                                                <div class="text-xs text-blue-600">
                                                    Gewichteter Durchschnitt aus {{ $deal->billables->count() }} Billable(s) basierend auf deren Werten
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div>
                                        <label class="font-semibold">
                                            {{ $deal->hasBillables() ? 'Wahrscheinlichkeit (gewichtet):' : 'Wahrscheinlichkeit:' }}
                                        </label>
                                        <div class="p-4 bg-gradient-to-r from-gray-50 to-blue-50 border border-gray-200 rounded-lg">
                                            <div class="text-2xl font-bold text-gray-800">
                                                {{ $deal->calculated_probability ? $deal->calculated_probability . '%' : '–' }}
                                            </div>
                                            @if($deal->hasBillables())
                                                <div class="text-sm text-blue-600 mt-1">
                                                    Berechnet aus {{ $deal->billables->count() }} Billable(s)
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endcan
                            </div>

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
                                        <label class="font-semibold">Deal Quelle:</label>
                                        <div class="p-2 bg-muted-5 rounded-lg">{{ $deal->dealSource?->label ?: '–' }}</div>
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
                                        <label class="font-semibold">Deal Typ:</label>
                                        <div class="p-2 bg-muted-5 rounded-lg">{{ $deal->dealType?->label ?: '–' }}</div>
                                    </div>
                                @endcan
                            </div>

                            {{-- Billing Interval & Duration --}}
                            <div class="grid grid-cols-2 gap-4">
                                @can('update', $deal)
                                    <x-ui-input-select
                                        name="deal.billing_interval"
                                        label="Abrechnungsintervall"
                                        :options="collect([
                                            (object)['value' => 'one_time', 'label' => 'Einmalig'],
                                            (object)['value' => 'monthly', 'label' => 'Monatlich'],
                                            (object)['value' => 'quarterly', 'label' => 'Vierteljährlich'],
                                            (object)['value' => 'yearly', 'label' => 'Jährlich']
                                        ])"
                                        optionValue="value"
                                        optionLabel="label"
                                        wire:model.live="deal.billing_interval"
                                        :errorKey="'deal.billing_interval'"
                                    />
                                @else
                                    <div>
                                        <label class="font-semibold">Abrechnungsintervall:</label>
                                        <div class="p-2 bg-muted-5 rounded-lg">
                                            @switch($deal->billing_interval)
                                                @case('one_time') Einmalig @break
                                                @case('monthly') Monatlich @break
                                                @case('quarterly') Vierteljährlich @break
                                                @case('yearly') Jährlich @break
                                                @default –
                                            @endswitch
                                        </div>
                                    </div>
                                @endcan

                                @can('update', $deal)
                                    <x-ui-input-number
                                        name="deal.billing_duration_months"
                                        label="Laufzeit (Monate)"
                                        wire:model.live.debounce.500ms="deal.billing_duration_months"
                                        placeholder="z.B. 12"
                                        min="1"
                                        :nullable="true"
                                        :errorKey="'deal.billing_duration_months'"
                                    />
                                @else
                                    <div>
                                        <label class="font-semibold">Laufzeit:</label>
                                        <div class="p-2 bg-muted-5 rounded-lg">
                                            {{ $deal->billing_duration_months ? $deal->billing_duration_months . ' Monate' : '–' }}
                                        </div>
                                    </div>
                                @endcan
                            </div>

                            {{-- Monthly Recurring Value (nur bei wiederkehrenden Deals) --}}
                            @if($deal->billing_interval && $deal->billing_interval !== 'one_time')
                                <div class="grid grid-cols-1 gap-4">
                                    @can('update', $deal)
                                        <x-ui-input-number
                                            name="deal.monthly_recurring_value"
                                            label="Monatlicher wiederkehrender Wert (MRR)"
                                            wire:model.live.debounce.500ms="deal.monthly_recurring_value"
                                            placeholder="0.00"
                                            step="0.01"
                                            min="0"
                                            :nullable="true"
                                            :errorKey="'deal.monthly_recurring_value'"
                                        />
                                    @else
                                        <div>
                                            <label class="font-semibold">Monatlicher wiederkehrender Wert:</label>
                                            <div class="p-2 bg-muted-5 rounded-lg">
                                                {{ $deal->monthly_recurring_value ? number_format((float) $deal->monthly_recurring_value, 2, ',', '.') . ' €' : '–' }}
                                            </div>
                                        </div>
                                    @endcan
                                    
                                </div>
                            @endif

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
                                    <label class="font-semibold">Fälligkeitsdatum:</label>
                                    <div class="p-2 bg-muted-5 rounded-lg">
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
                                    <label class="font-semibold">Zugewiesen an:</label>
                                    <div class="p-2 bg-muted-5 rounded-lg">
                                        {{ $deal->userInCharge?->name ?? 'Niemand zugewiesen' }}
                                    </div>
                                </div>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Aktivitäten (immer unten) -->
        <div x-data="{ open: false }" class="flex-shrink-0 border-t border-muted">
            <div 
                @click="open = !open" 
                class="cursor-pointer border-top-1 border-top-solid border-top-muted border-bottom-1 border-bottom-solid border-bottom-muted p-2 text-center d-flex items-center justify-center gap-1 mx-2 shadow-lg"
            >
                AKTIVITÄTEN 
                <span class="text-xs">
                    {{$deal->activities->count()}}
                </span>
                <x-heroicon-o-chevron-double-down 
                    class="w-3 h-3" 
                    x-show="!open"
                />
                <x-heroicon-o-chevron-double-up 
                    class="w-3 h-3" 
                    x-show="open"
                />
            </div>
            <div x-show="open" class="p-2 max-h-xs overflow-y-auto">
                <livewire:activity-log.index
                    :model="$deal"
                    :key="get_class($deal) . '_' . $deal->id"
                />
            </div>
        </div>
    </div>

    <!-- Rechte Spalte -->
    <div class="min-w-80 w-80 d-flex flex-col border-left-1 border-left-solid border-left-muted">

        <div class="d-flex gap-2 border-top-1 border-bottom-1 border-muted border-top-solid border-bottom-solid p-3 flex-shrink-0 bg-gray-50">
            <x-heroicon-o-cog-6-tooth class="w-5 h-5 text-gray-600"/>
            <span class="font-medium text-gray-900">Deal Übersicht</span>
        </div>
        <div class="flex-grow-1 overflow-y-auto p-4">

            {{-- Quick Stats --}}
            <div class="mb-6">
                <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    @svg('heroicon-o-chart-bar', 'w-4 h-4')
                    Schnellübersicht
                </h3>
                <div class="space-y-3">
                    <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="text-xs text-blue-600 font-medium">Deal Wert</div>
                        <div class="text-lg font-bold text-blue-800">
                            {{ $deal->deal_value ? number_format((float) $deal->deal_value, 2, ',', '.') . ' €' : 'Nicht festgelegt' }}
                        </div>
                    </div>
                    <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                        <div class="text-xs text-green-600 font-medium">Wahrscheinlichkeit</div>
                        <div class="text-lg font-bold text-green-800">
                            {{ $deal->calculated_probability ? $deal->calculated_probability . '%' : 'Nicht festgelegt' }}
                        </div>
                    </div>
                    @if($deal->hasBillables())
                        <div class="p-3 bg-purple-50 border border-purple-200 rounded-lg">
                            <div class="text-xs text-purple-600 font-medium">Billables</div>
                            <div class="text-lg font-bold text-purple-800">
                                {{ $deal->billables->count() }} Komponente(n)
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Navigation Buttons --}}
            <div class="d-flex flex-col gap-2 mb-4">
                @if($deal->salesBoard)
                    @can('view', $deal->salesBoard)
                        <x-ui-button 
                            variant="secondary-outline" 
                            size="md" 
                            :href="route('sales.boards.show', $deal->salesBoard)" 
                            wire:navigate
                            class="w-full d-flex"
                        >
                            <div class="d-flex items-center gap-2">
                                @svg('heroicon-o-arrow-left', 'w-4 h-4')
                                Board: {{ $deal->salesBoard?->name }}
                            </div>
                        </x-ui-button>
                    @else
                        <x-ui-button 
                            variant="secondary-outline" 
                            size="md" 
                            disabled="true"
                            title="Kein Zugriff auf das Board"
                            class="w-full d-flex"
                        >
                            <div class="d-flex items-center gap-2">
                                @svg('heroicon-o-arrow-left', 'w-4 h-4')
                                Board: {{ $deal->salesBoard?->name }}
                            </div>
                        </x-ui-button>
                    @endcan
                @endif
                <x-ui-button 
                    variant="secondary-outline" 
                    size="md" 
                    :href="route('sales.my-deals')" 
                    wire:navigate
                    class="w-full d-flex"
                >
                    <div class="d-flex items-center gap-2">
                        @svg('heroicon-o-arrow-left', 'w-4 h-4')
                        Meine Deals
                    </div>
                </x-ui-button>
            </div>

            {{-- Deal Status --}}
            <div class="mb-6">
                <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    @svg('heroicon-o-flag', 'w-4 h-4')
                    Deal Status
                </h3>
                
                {{-- Gewonnen-Checkbox --}}
                @can('update', $deal)
                    <div class="p-3 bg-gray-50 rounded-lg">
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
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <x-ui-badge variant="{{ $deal->is_done ? 'success' : 'gray' }}" size="lg" class="w-full justify-center">
                            @svg('heroicon-o-check-circle', 'w-4 h-4')
                            {{ $deal->is_done ? 'Deal gewonnen' : 'Deal offen' }}
                        </x-ui-badge>
                    </div>
                @endcan
                
                @if($deal->is_done)
                    <div class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center gap-2">
                            @svg('heroicon-o-check-circle', 'w-5 h-5 text-green-600')
                            <span class="text-sm font-medium text-green-800">Deal erfolgreich abgeschlossen</span>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Deal Info --}}
            <div class="mb-4">
                <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    @svg('heroicon-o-information-circle', 'w-4 h-4')
                    Deal Info
                </h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                        <span class="text-gray-600">Erstellt:</span>
                        <span class="font-medium">{{ $deal->created_at->format('d.m.Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                        <span class="text-gray-600">Geändert:</span>
                        <span class="font-medium">{{ $deal->updated_at->format('d.m.Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                        <span class="text-gray-600">Erstellt von:</span>
                        <span class="font-medium">{{ $deal->user?->name ?? 'Unbekannt' }}</span>
                    </div>
                    @if($deal->userInCharge)
                        <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                            <span class="text-gray-600">Zugewiesen an:</span>
                            <span class="font-medium">{{ $deal->userInCharge->name }}</span>
                        </div>
                    @endif
                    <div class="p-2 bg-gray-50 rounded">
                        <div class="text-gray-600 mb-1">Deal ID:</div>
                        <div class="font-mono text-xs text-gray-800 break-all">{{ $deal->uuid }}</div>
                    </div>
                </div>
            </div>

            <hr>

            {{-- Löschen-Buttons --}}
            @can('delete', $deal)
                <div class="d-flex flex-col gap-2">
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
            @endcan
        </div>
    </div>

    <!-- Billables Modal -->
    <livewire:sales.billables-modal />
</div>
