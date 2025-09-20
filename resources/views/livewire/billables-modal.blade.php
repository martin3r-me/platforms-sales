<div>
@if($modalShow)
<x-ui-modal size="lg" wire:model="modalShow">
    <x-slot name="header">
        Billables verwalten: {{ $deal?->title ?? 'Deal' }}
    </x-slot>

    <div class="space-y-4">
        {{-- Info --}}
        <div class="p-3 bg-blue-50 border border-blue-200 rounded">
            <div class="text-sm text-blue-600">
                💡 <strong>Billables</strong> ermöglichen es, komplexe Deals in mehrere Komponenten aufzuteilen:
            </div>
            <ul class="text-xs text-blue-600 mt-1 ml-4">
                <li>• <strong>Einmalig:</strong> Setup-Gebühr, Abschluss-Bonus, etc.</li>
                <li>• <strong>Wiederkehrend:</strong> Monatliche Lizenz, Beratung, etc.</li>
            </ul>
        </div>

        {{-- Billables Liste --}}
        <div class="space-y-3">
            @forelse($billables as $index => $billable)
                <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
                    <div class="grid grid-cols-12 gap-3 items-start">
                        {{-- Name --}}
                        <div class="col-span-4">
                            <x-ui-input-text
                                :name="'billables.' . $index . '.name'"
                                label="Name"
                                wire:model.live="billables.{{ $index }}.name"
                                placeholder="z.B. Setup-Gebühr"
                            />
                        </div>

                        {{-- Betrag --}}
                        <div class="col-span-2">
                            <x-ui-input-number
                                :name="'billables.' . $index . '.amount'"
                                label="Betrag (€)"
                                wire:model.live="billables.{{ $index }}.amount"
                                placeholder="0.00"
                                step="0.01"
                                min="0"
                            />
                        </div>

                        {{-- Typ --}}
                        <div class="col-span-2">
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
                        <div class="col-span-2">
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
                                <div class="text-sm text-gray-500 pt-6">–</div>
                            @endif
                        </div>

                        {{-- Laufzeit (nur bei wiederkehrend) --}}
                        <div class="col-span-1">
                            @if($billable['billing_type'] === 'recurring')
                                <x-ui-input-number
                                    :name="'billables.' . $index . '.duration_months'"
                                    label="Monate"
                                    wire:model.live="billables.{{ $index }}.duration_months"
                                    placeholder="12"
                                    min="1"
                                />
                            @else
                                <div class="text-sm text-gray-500 pt-6">–</div>
                            @endif
                        </div>

                        {{-- Löschen --}}
                        <div class="col-span-1 flex items-end">
                            <x-ui-button 
                                variant="danger-outline" 
                                size="sm" 
                                wire:click="removeBillable({{ $index }})"
                                class="w-full">
                                @svg('heroicon-o-trash', 'w-4 h-4')
                            </x-ui-button>
                        </div>
                    </div>

                    {{-- Beschreibung --}}
                    <div class="mt-3">
                        <x-ui-input-textarea
                            :name="'billables.' . $index . '.description'"
                            label="Beschreibung"
                            wire:model.live="billables.{{ $index }}.description"
                            placeholder="Optionale Beschreibung..."
                            rows="2"
                        />
                    </div>

                    {{-- Berechneter Gesamtwert --}}
                    @if($billable['billing_type'] === 'recurring' && $billable['amount'] > 0 && $billable['duration_months'])
                        <div class="mt-2 p-2 bg-green-50 border border-green-200 rounded">
                            <div class="text-sm text-green-600">
                                Gesamtwert: {{ number_format((float) $billable['amount'] * (int) $billable['duration_months'], 2, ',', '.') }} €
                                ({{ number_format((float) $billable['amount'], 2, ',', '.') }} € × {{ $billable['duration_months'] }} Monate)
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center py-8 text-gray-500">
                    <div class="text-lg mb-2">📊</div>
                    <div>Noch keine Billables vorhanden</div>
                    <div class="text-sm">Klicke auf "Billable hinzufügen" um zu beginnen</div>
                </div>
            @endforelse
        </div>

        {{-- Gesamtwert --}}
        @if(count($billables) > 0)
            <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="text-sm text-green-600">Gesamtwert aller Billables:</div>
                <div class="text-2xl font-bold text-green-800">
                    @php
                        $totalValue = 0;
                        foreach($billables as $billable) {
                            if ($billable['amount'] > 0) {
                                if ($billable['billing_type'] === 'recurring' && $billable['duration_months']) {
                                    $totalValue += (float) $billable['amount'] * (int) $billable['duration_months'];
                                } else {
                                    $totalValue += (float) $billable['amount'];
                                }
                            }
                        }
                    @endphp
                    {{ number_format($totalValue, 2, ',', '.') }} €
                </div>
            </div>
        @endif
    </div>

    <x-slot name="footer">
        <div class="d-flex justify-between">
            <x-ui-button variant="secondary-outline" wire:click="addBillable">
                @svg('heroicon-o-plus', 'w-4 h-4')
                Billable hinzufügen
            </x-ui-button>
            <div class="d-flex gap-2">
                <x-ui-button variant="secondary" wire:click="closeBillablesModal">
                    Abbrechen
                </x-ui-button>
                <x-ui-button variant="primary" wire:click="saveBillables">
                    Speichern
                </x-ui-button>
            </div>
        </div>
    </x-slot>
</x-ui-modal>
@endif
</div>
