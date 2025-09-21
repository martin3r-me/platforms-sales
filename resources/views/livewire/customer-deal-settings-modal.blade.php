<x-ui-modal size="lg" wire:model="modalShow">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">CRM Verknüpfung</h3>
                <p class="text-sm text-gray-600 mt-1">{{ $deal?->title ?? 'Deal' }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if($deal && $deal->companies()->count() > 0)
                    <x-ui-badge variant="green" size="sm">
                        {{ $deal->companies()->count() }} Company(s)
                    </x-ui-badge>
                @endif
            </div>
        </div>
    </x-slot>

    @if($deal)
        <div class="p-6 space-y-6">
            {{-- Info Box --}}
            <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                        @svg('heroicon-o-information-circle', 'w-5 h-5 text-blue-600')
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-blue-900 mb-2">CRM Integration</h4>
                        <p class="text-sm text-blue-700 mb-2">
                            Verknüpfe diesen Deal mit Companies aus dem CRM für bessere Übersicht und Reporting.
                        </p>
                        <div class="text-xs text-blue-600">
                            <div class="flex items-center gap-1">
                                <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                <strong>Company:</strong> Firma/Unternehmen aus dem CRM
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Company Selection --}}
            <div class="space-y-4">
                <h4 class="text-lg font-medium text-gray-900 flex items-center gap-2">
                    @svg('heroicon-o-building-office', 'w-5 h-5')
                    Company verknüpfen
                </h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-ui-input-text 
                            name="companySearch"
                            label="Company suchen"
                            wire:model.live.debounce.300ms="companySearch"
                            placeholder="Firma suchen..."
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Company auswählen</label>
                        <select class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" wire:model.live="companyId">
                            <option value="">– Keine Company –</option>
                            @foreach($companyOptions as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <div class="p-3 bg-gray-50 rounded-lg">
                    <div class="text-sm font-medium text-gray-700 mb-1">Aktuelle Auswahl:</div>
                    <div class="text-sm text-gray-600">{{ $companyDisplay ?? 'Keine Company ausgewählt' }}</div>
                </div>
            </div>


            {{-- Current Links Summary --}}
            @if($deal->companies()->count() > 0)
                <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                    <h5 class="text-sm font-semibold text-green-900 mb-3 flex items-center gap-2">
                        @svg('heroicon-o-check-circle', 'w-4 h-4')
                        Aktuelle Verknüpfungen
                    </h5>
                    <div class="space-y-2">
                        @foreach($deal->companies() as $company)
                            <div class="flex items-center justify-between p-2 bg-white rounded border border-green-100">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-building-office', 'w-4 h-4 text-green-600')
                                    <span class="text-sm font-medium text-green-800">{{ $company->name }}</span>
                                </div>
                                <x-ui-badge variant="green" size="sm">Company</x-ui-badge>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    <x-slot name="footer">
        <div class="flex items-center justify-between">
            <x-ui-button variant="secondary-outline" wire:click="closeModal">
                Abbrechen
            </x-ui-button>
            <x-ui-button variant="primary" wire:click="saveCompany" class="flex items-center gap-2">
                @svg('heroicon-o-link', 'w-4 h-4')
                Company verknüpfen
            </x-ui-button>
        </div>
    </x-slot>
</x-ui-modal>
