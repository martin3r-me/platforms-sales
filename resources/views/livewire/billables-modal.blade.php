<div>
@if($modalShow)
    <x-ui-modal size="xl" wire:model="modalShow">
        <x-slot name="header">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Billables verwalten</h3>
                    <p class="text-sm text-gray-600 mt-1">{{ $deal?->title ?? 'Deal' }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <x-ui-badge variant="blue" size="sm">
                        {{ count($billables) }} Billable(s)
                    </x-ui-badge>
                </div>
            </div>
        </x-slot>

        <div class="space-y-6">
            {{-- Info --}}
            <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                        @svg('heroicon-o-light-bulb', 'w-5 h-5 text-blue-600')
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-blue-900 mb-2">Billables verstehen</h4>
                        <p class="text-sm text-blue-700 mb-2">
                            Teile komplexe Deals in einzelne Komponenten auf für präzise Wertberechnung:
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs text-blue-600">
                            <div class="flex items-center gap-1">
                                <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                <strong>Einmalig:</strong> Setup, Abschluss-Bonus, Hardware
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="w-2 h-2 bg-indigo-500 rounded-full"></span>
                                <strong>Wiederkehrend:</strong> Lizenzen, Support, Beratung
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Billables Liste --}}
            <div class="space-y-4">
                @forelse($billables as $index => $billable)
                    <div class="p-5 border border-[var(--ui-border)] rounded-xl bg-white shadow-sm hover:shadow-md transition-shadow">
                        {{-- Header --}}
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-[var(--ui-primary-5)] rounded-full flex items-center justify-center border border-[var(--ui-primary)]/30">
                                    <span class="text-sm font-semibold text-[var(--ui-primary)]">{{ $index + 1 }}</span>
                                </div>
                                <h4 class="font-medium text-[var(--ui-secondary)]">Billable #{{ $index + 1 }}</h4>
                                @if(($billable['billing_type'] ?? 'one_time') === 'recurring')
                                    <x-ui-badge variant="success" size="xs">
                                        Wiederkehrend
                                    </x-ui-badge>
                                @else
                                    <x-ui-badge variant="neutral" size="xs">
                                        Einmalig
                                    </x-ui-badge>
                                @endif
                            </div>
                            <x-ui-button 
                                variant="danger-outline" 
                                size="sm" 
                                wire:click="removeBillable({{ $index }})"
                            >
                                @svg('heroicon-o-trash', 'w-4 h-4')
                            </x-ui-button>
                        </div>

                        {{-- Form Fields --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            {{-- Name --}}
                            <div class="md:col-span-2">
                                <x-ui-input-text
                                    :name="'billables.' . $index . '.name'"
                                    label="Name"
                                    wire:model.live="billables.{{ $index }}.name"
                                    placeholder="z.B. Setup-Gebühr, Monatliche Lizenz"
                                />
                            </div>

                            {{-- Betrag --}}
                            <div>
                                <x-ui-input-number
                                    :name="'billables.' . $index . '.amount'"
                                    label="Betrag (€)"
                                    wire:model.live="billables.{{ $index }}.amount"
                                    placeholder="0.00"
                                    step="0.01"
                                    min="0"
                                />
                            </div>

                            {{-- Wahrscheinlichkeit --}}
                            <div>
                                <x-ui-input-number
                                    :name="'billables.' . $index . '.probability_percent'"
                                    label="Wahrscheinlichkeit (%)"
                                    wire:model.live="billables.{{ $index }}.probability_percent"
                                    placeholder="100"
                                    min="0"
                                    max="100"
                                />
                            </div>

                            {{-- Typ --}}
                            <div>
                                <x-ui-input-select
                                    :name="'billables.' . $index . '.billing_type'"
                                    label="Typ"
                                    :options="collect([
                                        (object)['value' => 'one_time', 'label' => 'Einmalig'],
                                        (object)['value' => 'recurring', 'label' => 'Wiederkehrend']
                                    ])"
                                    optionValue="value"
                                    optionLabel="label"
                                    wire:model.live="billables.{{ $index }}.billing_type"
                                />
                            </div>

                            {{-- Intervall (nur bei wiederkehrend) --}}
                            <div>
                                @if($billable['billing_type'] === 'recurring')
                                    <x-ui-input-select
                                        :name="'billables.' . $index . '.billing_interval'"
                                        label="Intervall"
                                        :options="collect([
                                            (object)['value' => 'monthly', 'label' => 'Monatlich'],
                                            (object)['value' => 'quarterly', 'label' => 'Vierteljährlich'],
                                            (object)['value' => 'yearly', 'label' => 'Jährlich']
                                        ])"
                                        optionValue="value"
                                        optionLabel="label"
                                        wire:model.live="billables.{{ $index }}.billing_interval"
                                    />
                                @else
                                    <div class="pt-6">
                                        <label class="block text-sm font-medium text-[var(--ui-muted)] mb-1">Intervall</label>
                                        <div class="text-sm text-[var(--ui-muted)] italic">Nur bei wiederkehrend</div>
                                    </div>
                                @endif
                            </div>

                            {{-- Laufzeit (nur bei wiederkehrend) --}}
                            <div>
                                @if(($billable['billing_type'] ?? 'one_time') === 'recurring')
                                    <x-ui-input-number
                                        :name="'billables.' . $index . '.duration_months'"
                                        label="Laufzeit (Monate)"
                                        wire:model.live="billables.{{ $index }}.duration_months"
                                        placeholder="12"
                                        min="1"
                                    />
                                    <p class="text-xs text-[var(--ui-muted)] mt-1">
                                        Gesamtwert = Betrag × Laufzeit (z.B. 100€ × 12 = 1.200€)
                                    </p>
                                @else
                                    <div class="pt-6">
                                        <label class="block text-sm font-medium text-[var(--ui-muted)] mb-1">Laufzeit</label>
                                        <div class="text-sm text-[var(--ui-muted)] italic">Nur bei wiederkehrend</div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Beschreibung --}}
                        <div class="mt-4">
                            <x-ui-input-textarea
                                :name="'billables.' . $index . '.description'"
                                label="Beschreibung (optional)"
                                wire:model.live="billables.{{ $index }}.description"
                                placeholder="Zusätzliche Details zu diesem Billable..."
                                rows="2"
                            />
                        </div>

                        {{-- Berechnete Werte --}}
                        @if(($billable['amount'] ?? 0) > 0)
                            <div class="mt-4 p-4 bg-gradient-to-r from-[var(--ui-primary-5)] to-[var(--ui-success-5)] border border-[var(--ui-primary)]/30 rounded-lg">
                                <h5 class="text-sm font-semibold text-[var(--ui-secondary)] mb-3 flex items-center gap-2">
                                    @svg('heroicon-o-calculator', 'w-4 h-4')
                                    Berechnete Werte
                                </h5>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="p-3 bg-white rounded-lg border border-[var(--ui-primary)]/20 shadow-sm">
                                        <div class="text-xs text-[var(--ui-primary)] font-medium mb-1">Gesamtwert</div>
                                        <div class="text-lg font-bold text-[var(--ui-primary)]">
                                            @php
                                                $totalValue = ($billable['billing_type'] ?? 'one_time') === 'recurring' && isset($billable['duration_months']) && $billable['duration_months'] > 0
                                                    ? (float) $billable['amount'] * (int) $billable['duration_months']
                                                    : (float) $billable['amount'];
                                            @endphp
                                            {{ number_format($totalValue, 2, ',', '.') }} €
                                        </div>
                                        @if(($billable['billing_type'] ?? 'one_time') === 'recurring' && isset($billable['duration_months']) && $billable['duration_months'] > 0)
                                            <div class="text-xs text-[var(--ui-muted)] mt-1">
                                                {{ number_format((float) $billable['amount'], 2, ',', '.') }} € × {{ $billable['duration_months'] }} Monate
                                            </div>
                                        @endif
                                    </div>
                                    <div class="p-3 bg-white rounded-lg border border-[var(--ui-success)]/20 shadow-sm">
                                        <div class="text-xs text-[var(--ui-success)] font-medium mb-1">Erwarteter Wert</div>
                                        <div class="text-lg font-bold text-[var(--ui-success)]">
                                            @php
                                                $probability = (int) ($billable['probability_percent'] ?? 100);
                                                $expectedValue = $totalValue * $probability / 100;
                                            @endphp
                                            {{ number_format($expectedValue, 2, ',', '.') }} €
                                        </div>
                                        <div class="text-xs text-[var(--ui-muted)] mt-1">
                                            {{ $probability }}% Wahrscheinlichkeit
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                </div>
                @empty
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-[var(--ui-muted-5)] rounded-full flex items-center justify-center mx-auto mb-4">
                            @svg('heroicon-o-calculator', 'w-8 h-8 text-[var(--ui-muted)]')
                        </div>
                        <h3 class="text-lg font-medium text-[var(--ui-secondary)] mb-2">Noch keine Billables</h3>
                        <p class="text-sm text-[var(--ui-muted)] mb-4">Teile deinen Deal in einzelne Komponenten auf für präzise Wertberechnung</p>
                        <x-ui-button variant="primary" wire:click="addBillable">
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                <span>Ersten Billable hinzufügen</span>
                            </span>
                        </x-ui-button>
                    </div>
                @endforelse
            </div>

            {{-- Gesamtwerte --}}
            @if(count($billables) > 0)
                <div class="p-6 bg-gradient-to-r from-[var(--ui-muted-5)] to-[var(--ui-primary-5)] border border-[var(--ui-border)] rounded-xl">
                    <h4 class="text-lg font-semibold text-[var(--ui-secondary)] mb-4 flex items-center gap-2">
                        @svg('heroicon-o-chart-bar', 'w-5 h-5')
                        Deal-Zusammenfassung
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-4 bg-white rounded-lg border border-[var(--ui-primary)]/20 shadow-sm">
                            <div class="text-sm text-[var(--ui-primary)] font-medium mb-1">Gesamtwert</div>
                            <div class="text-2xl font-bold text-[var(--ui-primary)]">
                                @php
                                    $totalValue = 0;
                                    foreach($billables as $billable) {
                                        if (($billable['amount'] ?? 0) > 0) {
                                            if (($billable['billing_type'] ?? 'one_time') === 'recurring' && isset($billable['duration_months']) && $billable['duration_months'] > 0) {
                                                $totalValue += (float) $billable['amount'] * (int) $billable['duration_months'];
                                            } else {
                                                $totalValue += (float) $billable['amount'];
                                            }
                                        }
                                    }
                                @endphp
                                {{ number_format($totalValue, 2, ',', '.') }} €
                            </div>
                            <div class="text-xs text-[var(--ui-muted)] mt-1">Alle Billables zusammen</div>
                        </div>
                        
                        <div class="p-4 bg-white rounded-lg border border-[var(--ui-success)]/20 shadow-sm">
                            <div class="text-sm text-[var(--ui-success)] font-medium mb-1">Erwarteter Wert</div>
                            <div class="text-2xl font-bold text-[var(--ui-success)]">
                                @php
                                    $expectedTotalValue = 0;
                                    $weightedProbabilitySum = 0;
                                    $totalValue = 0;
                                    
                                    foreach($billables as $billable) {
                                        if (($billable['amount'] ?? 0) > 0) {
                                            $billableTotal = (($billable['billing_type'] ?? 'one_time') === 'recurring' && isset($billable['duration_months']) && $billable['duration_months'] > 0)
                                                ? (float) $billable['amount'] * (int) $billable['duration_months']
                                                : (float) $billable['amount'];
                                            
                                            $probability = (int) ($billable['probability_percent'] ?? 100);
                                            $expectedTotalValue += $billableTotal * $probability / 100;
                                            
                                            // Für gewichteten Durchschnitt
                                            $weightedProbabilitySum += $probability * $billableTotal;
                                            $totalValue += $billableTotal;
                                        }
                                    }
                                    
                                    $weightedAverage = $totalValue > 0 ? round($weightedProbabilitySum / $totalValue, 1) : 0;
                                @endphp
                                {{ number_format($expectedTotalValue, 2, ',', '.') }} €
                            </div>
                            <div class="text-xs text-[var(--ui-muted)] mt-1">Realistischer Erwartungswert</div>
                        </div>

                        <div class="p-4 bg-white rounded-lg border border-[var(--ui-primary)]/20 shadow-sm">
                            <div class="text-sm text-[var(--ui-primary)] font-medium mb-1">Gewichtete Wahrscheinlichkeit</div>
                            <div class="text-2xl font-bold text-[var(--ui-primary)]">
                                {{ $weightedAverage }}%
                            </div>
                            <div class="text-xs text-[var(--ui-muted)] mt-1">Durchschnitt aller Billables</div>
                        </div>
                    </div>
                </div>
            @endif
    </div>

    <x-slot name="footer">
        <div class="flex items-center justify-between">
            <x-ui-button variant="secondary-outline" wire:click="addBillable" class="flex items-center gap-2">
                @svg('heroicon-o-plus', 'w-4 h-4')
                Billable hinzufügen
            </x-ui-button>
            <div class="flex items-center gap-3">
                <x-ui-button variant="secondary" wire:click="closeModal">
                    Abbrechen
                </x-ui-button>
                <x-ui-button variant="primary" wire:click="saveBillables" class="flex items-center gap-2">
                    @svg('heroicon-o-check', 'w-4 h-4')
                    Speichern & Schließen
                </x-ui-button>
            </div>
        </div>
    </x-slot>
</x-ui-modal>
@endif
</div>
