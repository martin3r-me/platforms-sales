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
            

            {{-- Deal Form --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4 text-secondary d-flex items-center gap-2">
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

                    {{-- Billables Management --}}
                    <div class="grid grid-cols-1 gap-4">
                        <div class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h4 class="text-lg font-semibold text-green-800 mb-1">Billables verwalten</h4>
                                    <p class="text-sm text-green-600">Teile deinen Deal in einzelne Komponenten auf</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($deal->hasBillables())
                                        <x-ui-badge variant="green" size="sm">
                                            {{ $deal->billables->count() }} Billable(s)
                                        </x-ui-badge>
                                    @endif
                                </div>
                            </div>
                            
                            <x-ui-button 
                                variant="success" 
                                size="lg" 
                                wire:click="openBillablesModal"
                                class="w-full flex items-center justify-center gap-2">
                                @svg('heroicon-o-calculator', 'w-5 h-5')
                                {{ $deal->hasBillables() ? 'Billables bearbeiten' : 'Billables hinzufügen' }}
                            </x-ui-button>
                            
                            @if($deal->hasBillables())
                                <div class="mt-3 grid grid-cols-2 gap-3">
                                    <div class="p-2 bg-white rounded border border-green-100">
                                        <div class="text-xs text-green-600 font-medium">Gesamtwert</div>
                                        <div class="text-sm font-bold text-green-800">
                                            {{ number_format((float) $deal->deal_value, 2, ',', '.') }} €
                                        </div>
                                    </div>
                                    <div class="p-2 bg-white rounded border border-green-100">
                                        <div class="text-xs text-green-600 font-medium">Erwarteter Wert</div>
                                        <div class="text-sm font-bold text-green-800">
                                            {{ number_format((float) $deal->billables_expected_total, 2, ',', '.') }} €
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- CRM Integration --}}
                    <div class="grid grid-cols-1 gap-4">
                        <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h4 class="text-lg font-semibold text-blue-800 mb-1">CRM Verknüpfung</h4>
                                    <p class="text-sm text-blue-600">Verknüpfe mit Companies und Contacts</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($deal->companies()->count() > 0 || $deal->contacts()->count() > 0)
                                        <x-ui-badge variant="blue" size="sm">
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
                                        <div class="p-2 bg-white rounded border border-blue-100 flex items-center gap-2">
                                            @svg('heroicon-o-building-office', 'w-4 h-4 text-blue-600')
                                            <span class="text-sm font-medium text-blue-800">{{ $company->name }}</span>
                                            <x-ui-badge variant="blue" size="xs">Company</x-ui-badge>
                                        </div>
                                    @endforeach
                                    @foreach($deal->contacts() as $contact)
                                        <div class="p-2 bg-white rounded border border-blue-100 flex items-center gap-2">
                                            @svg('heroicon-o-user', 'w-4 h-4 text-blue-600')
                                            <span class="text-sm font-medium text-blue-800">{{ $contact->display_name }}</span>
                                            <x-ui-badge variant="indigo" size="xs">Contact</x-ui-badge>
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
    
    <!-- Customer Deal Settings Modal -->
    <livewire:sales.customer-deal-settings-modal />
</div>
