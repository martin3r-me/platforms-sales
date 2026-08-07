<div>
@if($modalShow)
    <x-nx-modal size="xl" wire:model="modalShow">
        <x-slot name="header">
            <div class="flex items-center gap-3">
                <div class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-[var(--nx-accent)]/10 flex-shrink-0">
                    @svg('heroicon-o-calculator', 'w-5 h-5 text-[var(--nx-accent)]')
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-base font-semibold text-[var(--nx-text)] m-0 leading-tight">Billables verwalten</h3>
                    <p class="text-[12px] text-[var(--nx-muted)] m-0 mt-0.5 truncate">{{ $deal?->title ?? 'Deal' }}</p>
                </div>
                <x-nx-badge variant="info">
                    {{ count($billables) }} Billable(s)
                </x-nx-badge>
            </div>
        </x-slot>

        <div class="space-y-6">
            {{-- Info --}}
            <x-nx-callout variant="info" title="Billables verstehen">
                Teile komplexe Deals in einzelne Komponenten auf für präzise Wertberechnung:
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs mt-2">
                    <div class="flex items-center gap-1">
                        <span class="w-2 h-2 bg-[color:var(--nx-info)] rounded-full"></span>
                        <strong>Einmalig:</strong> Setup, Abschluss-Bonus, Hardware
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="w-2 h-2 bg-[color:var(--nx-success)] rounded-full"></span>
                        <strong>Wiederkehrend:</strong> Lizenzen, Support, Beratung
                    </div>
                </div>
            </x-nx-callout>

            {{-- Billables Liste --}}
            <div class="space-y-4">
                @forelse($billables as $index => $billable)
                    <x-nx-card>
                        {{-- Header --}}
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-[var(--nx-accent)]/10 rounded-full flex items-center justify-center border border-[color:var(--nx-line)]">
                                    <span class="text-sm font-semibold text-[var(--nx-accent)]">{{ $index + 1 }}</span>
                                </div>
                                <h4 class="font-medium text-[var(--nx-text)]">Billable #{{ $index + 1 }}</h4>
                                @if(($billable['billing_type'] ?? 'one_time') === 'recurring')
                                    <x-nx-badge variant="success">
                                        Wiederkehrend
                                    </x-nx-badge>
                                @else
                                    <x-nx-badge variant="neutral">
                                        Einmalig
                                    </x-nx-badge>
                                @endif
                            </div>
                            <x-nx-button
                                variant="danger"
                                size="sm"
                                icon
                                wire:click="removeBillable({{ $index }})"
                            >
                                @svg('heroicon-o-trash', 'w-4 h-4')
                            </x-nx-button>
                        </div>

                        {{-- Form Fields --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            {{-- Name --}}
                            <div class="md:col-span-2">
                                <x-nx-input-text
                                    :name="'billables.' . $index . '.name'"
                                    label="Name"
                                    wire:model.live="billables.{{ $index }}.name"
                                    placeholder="z.B. Setup-Gebühr, Monatliche Lizenz"
                                />
                            </div>

                            {{-- Betrag --}}
                            <div>
                                <x-nx-input-number
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
                                <x-nx-input-number
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
                                <x-nx-input-select
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
                                    <x-nx-input-select
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
                                        <label class="block text-xs font-medium text-[var(--nx-muted)] mb-1">Intervall</label>
                                        <div class="text-sm text-[var(--nx-muted)] italic">Nur bei wiederkehrend</div>
                                    </div>
                                @endif
                            </div>

                            {{-- Laufzeit (nur bei wiederkehrend) --}}
                            <div>
                                @if(($billable['billing_type'] ?? 'one_time') === 'recurring')
                                    <x-nx-input-number
                                        :name="'billables.' . $index . '.duration_months'"
                                        label="Laufzeit (Monate)"
                                        wire:model.live="billables.{{ $index }}.duration_months"
                                        placeholder="12"
                                        min="1"
                                    />
                                    <p class="text-xs text-[var(--nx-muted)] mt-1">
                                        @if(($billable['billing_interval'] ?? 'monthly') === 'quarterly')
                                            Gesamtwert = Betrag × Quartale (Monate ÷ 3)
                                        @elseif(($billable['billing_interval'] ?? 'monthly') === 'yearly')
                                            Gesamtwert = Betrag × Jahre (Monate ÷ 12)
                                        @else
                                            Gesamtwert = Betrag × Monate (z.B. 100€ × 12 = 1.200€)
                                        @endif
                                    </p>
                                @else
                                    <div class="pt-6">
                                        <label class="block text-xs font-medium text-[var(--nx-muted)] mb-1">Laufzeit</label>
                                        <div class="text-sm text-[var(--nx-muted)] italic">Nur bei wiederkehrend</div>
                                    </div>
                                @endif
                            </div>

                            {{-- Startdatum / Zahlungsdatum --}}
                            <div>
                                <x-nx-input-date
                                    :name="'billables.' . $index . '.start_date'"
                                    :label="($billable['billing_type'] ?? 'one_time') === 'recurring' ? 'Startdatum' : 'Zahlungsdatum'"
                                    wire:model.live="billables.{{ $index }}.start_date"
                                    :nullable="true"
                                />
                                <p class="text-xs text-[var(--nx-muted)] mt-1">
                                    @if(($billable['billing_type'] ?? 'one_time') === 'recurring')
                                        Ab wann laufen die Zahlungen?
                                    @else
                                        Wann wird der Betrag fällig?
                                    @endif
                                </p>
                            </div>
                        </div>

                        {{-- Beschreibung --}}
                        <div class="mt-4">
                            <x-nx-input-textarea
                                :name="'billables.' . $index . '.description'"
                                label="Beschreibung (optional)"
                                wire:model.live="billables.{{ $index }}.description"
                                placeholder="Zusätzliche Details zu diesem Billable..."
                                rows="2"
                            />
                        </div>

                        {{-- Berechnete Werte --}}
                        @if(($billable['amount'] ?? 0) > 0)
                            <div class="mt-4 p-4 rounded-lg border border-[color:var(--nx-line)]" style="background:rgba(25,113,194,.06)">
                                <h5 class="text-sm font-semibold text-[var(--nx-text)] mb-3 flex items-center gap-2">
                                    @svg('heroicon-o-calculator', 'w-4 h-4')
                                    Berechnete Werte
                                </h5>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="p-3 bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)]">
                                        <div class="text-xs text-[color:var(--nx-info)] font-medium mb-1">Gesamtwert</div>
                                        <div class="text-lg font-bold text-[color:var(--nx-info)]">
                                            @php
                                                $intervalMonths = match($billable['billing_interval'] ?? 'monthly') {
                                                    'quarterly' => 3,
                                                    'yearly' => 12,
                                                    default => 1,
                                                };
                                                $totalValue = ($billable['billing_type'] ?? 'one_time') === 'recurring' && isset($billable['duration_months']) && $billable['duration_months'] > 0
                                                    ? (float) $billable['amount'] * (int) $billable['duration_months'] / $intervalMonths
                                                    : (float) $billable['amount'];
                                            @endphp
                                            {{ number_format($totalValue, 2, ',', '.') }} €
                                        </div>
                                        @if(($billable['billing_type'] ?? 'one_time') === 'recurring' && isset($billable['duration_months']) && $billable['duration_months'] > 0)
                                            @php
                                                $periods = (int) $billable['duration_months'] / $intervalMonths;
                                                $periodsFormatted = $periods == floor($periods) ? (int) $periods : number_format($periods, 1, ',', '.');
                                                $periodLabel = match($billable['billing_interval'] ?? 'monthly') {
                                                    'quarterly' => 'Quartale',
                                                    'yearly' => ($periods == 1 ? 'Jahr' : 'Jahre'),
                                                    default => 'Monate',
                                                };
                                            @endphp
                                            <div class="text-xs text-[var(--nx-muted)] mt-1">
                                                {{ number_format((float) $billable['amount'], 2, ',', '.') }} € × {{ $periodsFormatted }} {{ $periodLabel }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="p-3 bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)]">
                                        <div class="text-xs text-[color:var(--nx-success)] font-medium mb-1">Erwarteter Wert</div>
                                        <div class="text-lg font-bold text-[color:var(--nx-success)]">
                                            @php
                                                $probability = (int) ($billable['probability_percent'] ?? 100);
                                                $expectedValue = $totalValue * $probability / 100;
                                            @endphp
                                            {{ number_format($expectedValue, 2, ',', '.') }} €
                                        </div>
                                        <div class="text-xs text-[var(--nx-muted)] mt-1">
                                            {{ $probability }}% Wahrscheinlichkeit
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </x-nx-card>
                @empty
                    <x-nx-empty icon="heroicon-o-calculator">
                        <div class="text-base font-medium text-[var(--nx-text)] mb-1">Noch keine Billables</div>
                        <div class="text-sm text-[var(--nx-muted)]">Teile deinen Deal in einzelne Komponenten auf für präzise Wertberechnung</div>
                        <x-slot name="action">
                            <x-nx-button variant="primary" wire:click="addBillable">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                <span>Ersten Billable hinzufügen</span>
                            </x-nx-button>
                        </x-slot>
                    </x-nx-empty>
                @endforelse
            </div>

            {{-- Gesamtwerte --}}
            @if(count($billables) > 0)
                <div class="p-6 rounded-xl border border-[color:var(--nx-line)] bg-[color:var(--nx-bg)]">
                    <h4 class="text-base font-semibold text-[var(--nx-text)] mb-4 flex items-center gap-2">
                        @svg('heroicon-o-chart-bar', 'w-5 h-5')
                        Deal-Zusammenfassung
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-4 bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)]">
                            <div class="text-sm text-[color:var(--nx-info)] font-medium mb-1">Gesamtwert</div>
                            <div class="text-2xl font-bold text-[color:var(--nx-info)] tabular-nums">
                                @php
                                    $totalValue = 0;
                                    foreach($billables as $billable) {
                                        if (($billable['amount'] ?? 0) > 0) {
                                            if (($billable['billing_type'] ?? 'one_time') === 'recurring' && isset($billable['duration_months']) && $billable['duration_months'] > 0) {
                                                $ivlMonths = match($billable['billing_interval'] ?? 'monthly') {
                                                    'quarterly' => 3,
                                                    'yearly' => 12,
                                                    default => 1,
                                                };
                                                $totalValue += (float) $billable['amount'] * (int) $billable['duration_months'] / $ivlMonths;
                                            } else {
                                                $totalValue += (float) $billable['amount'];
                                            }
                                        }
                                    }
                                @endphp
                                {{ number_format($totalValue, 2, ',', '.') }} €
                            </div>
                            <div class="text-xs text-[var(--nx-muted)] mt-1">Alle Billables zusammen</div>
                        </div>

                        <div class="p-4 bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)]">
                            <div class="text-sm text-[color:var(--nx-success)] font-medium mb-1">Erwarteter Wert</div>
                            <div class="text-2xl font-bold text-[color:var(--nx-success)] tabular-nums">
                                @php
                                    $expectedTotalValue = 0;
                                    $weightedProbabilitySum = 0;
                                    $totalValue = 0;

                                    foreach($billables as $billable) {
                                        if (($billable['amount'] ?? 0) > 0) {
                                            $ivlMonths = match($billable['billing_interval'] ?? 'monthly') {
                                                'quarterly' => 3,
                                                'yearly' => 12,
                                                default => 1,
                                            };
                                            $billableTotal = (($billable['billing_type'] ?? 'one_time') === 'recurring' && isset($billable['duration_months']) && $billable['duration_months'] > 0)
                                                ? (float) $billable['amount'] * (int) $billable['duration_months'] / $ivlMonths
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
                            <div class="text-xs text-[var(--nx-muted)] mt-1">Realistischer Erwartungswert</div>
                        </div>

                        <div class="p-4 bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)]">
                            <div class="text-sm text-[color:var(--nx-info)] font-medium mb-1">Gewichtete Wahrscheinlichkeit</div>
                            <div class="text-2xl font-bold text-[color:var(--nx-info)] tabular-nums">
                                {{ $weightedAverage }}%
                            </div>
                            <div class="text-xs text-[var(--nx-muted)] mt-1">Durchschnitt aller Billables</div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <x-slot name="footer">
            <div class="flex items-center justify-between w-full">
                <x-nx-button variant="secondary" wire:click="addBillable">
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    <span>Billable hinzufügen</span>
                </x-nx-button>
                <div class="flex items-center gap-2">
                    <x-nx-button variant="secondary" wire:click="closeModal">
                        Abbrechen
                    </x-nx-button>
                    <x-nx-button variant="primary" wire:click="saveBillables">
                        @svg('heroicon-o-check', 'w-4 h-4')
                        <span>Speichern & Schließen</span>
                    </x-nx-button>
                </div>
            </div>
        </x-slot>
    </x-nx-modal>
@endif
</div>
