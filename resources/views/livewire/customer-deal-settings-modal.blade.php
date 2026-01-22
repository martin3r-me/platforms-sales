<x-ui-modal size="lg" wire:model="modalShow">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-[var(--ui-secondary)]">CRM Verknüpfung</h3>
                <p class="text-sm text-[var(--ui-muted)] mt-1">{{ $deal?->title ?? 'Deal' }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if($deal && ($deal->companies()->count() > 0 || $deal->contacts()->count() > 0))
                    <x-ui-badge variant="success" size="sm">
                        {{ $deal->companies()->count() + $deal->contacts()->count() }} Verknüpfung(en)
                    </x-ui-badge>
                @endif
            </div>
        </div>
    </x-slot>

    @if($deal)
        <div class="p-6 space-y-6">
            {{-- Info Box --}}
            <div class="p-4 bg-gradient-to-r from-[var(--ui-primary-5)] to-[var(--ui-primary-10)] border border-[var(--ui-primary)]/30 rounded-lg">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                        @svg('heroicon-o-information-circle', 'w-5 h-5 text-[var(--ui-primary)]')
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-[var(--ui-primary)] mb-2">CRM Integration</h4>
                        <p class="text-sm text-[var(--ui-primary)]/90 mb-2">
                            Verknüpfe diesen Deal mit Companies und Contacts aus dem CRM für bessere Übersicht und Reporting.
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs text-[var(--ui-primary)]">
                            <div class="flex items-center gap-1">
                                <span class="w-2 h-2 bg-[var(--ui-primary)] rounded-full"></span>
                                <strong>Company:</strong> Firma/Unternehmen
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="w-2 h-2 bg-[var(--ui-success)] rounded-full"></span>
                                <strong>Contact:</strong> Ansprechpartner
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Company Selection --}}
            <div class="space-y-4">
                <h4 class="text-lg font-medium text-[var(--ui-secondary)] flex items-center gap-2">
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
                
                <div class="p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                    <div class="text-sm font-medium text-[var(--ui-secondary)] mb-1">Aktuelle Auswahl:</div>
                    <div class="text-sm text-[var(--ui-muted)]">{{ $companyDisplay ?? 'Keine Company ausgewählt' }}</div>
                </div>
            </div>

            {{-- Contact Selection --}}
            <div class="space-y-4">
                <h4 class="text-lg font-medium text-[var(--ui-secondary)] flex items-center gap-2">
                    @svg('heroicon-o-user', 'w-5 h-5')
                    Contact verknüpfen
                </h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-ui-input-text 
                            name="contactSearch"
                            label="Contact suchen"
                            wire:model.live.debounce.300ms="contactSearch"
                            placeholder="Ansprechpartner suchen..."
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Contact auswählen</label>
                        <select class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" wire:model.live="contactId">
                            <option value="">– Kein Contact –</option>
                            @foreach($contactOptions as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <div class="p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                    <div class="text-sm font-medium text-[var(--ui-secondary)] mb-1">Aktuelle Auswahl:</div>
                    <div class="text-sm text-[var(--ui-muted)]">{{ $contactDisplay ?? 'Kein Contact ausgewählt' }}</div>
                </div>
            </div>

            {{-- Current Links Summary --}}
            @if($deal->companies()->count() > 0 || $deal->contacts()->count() > 0)
                <div class="p-4 bg-[var(--ui-success-5)] border border-[var(--ui-success)]/30 rounded-lg">
                    <h5 class="text-sm font-semibold text-[var(--ui-success)] mb-3 flex items-center gap-2">
                        @svg('heroicon-o-check-circle', 'w-4 h-4')
                        Aktuelle Verknüpfungen
                    </h5>
                    <div class="space-y-2">
                        @foreach($deal->companies() as $company)
                            <div class="flex items-center justify-between p-2 bg-white rounded border border-[var(--ui-success)]/20">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-building-office', 'w-4 h-4 text-[var(--ui-success)]')
                                    <span class="text-sm font-medium text-[var(--ui-secondary)]">{{ $company->name }}</span>
                                </div>
                                <x-ui-badge variant="success" size="sm">Company</x-ui-badge>
                            </div>
                        @endforeach
                        @foreach($deal->contacts() as $contact)
                            <div class="flex items-center justify-between p-2 bg-white rounded border border-[var(--ui-success)]/20">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-user', 'w-4 h-4 text-[var(--ui-success)]')
                                    <span class="text-sm font-medium text-[var(--ui-secondary)]">{{ $contact->display_name }}</span>
                                </div>
                                <x-ui-badge variant="primary" size="sm">Contact</x-ui-badge>
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
            <x-ui-button variant="primary" wire:click="saveCompanyAndContact" class="flex items-center gap-2">
                @svg('heroicon-o-link', 'w-4 h-4')
                Verknüpfungen speichern
            </x-ui-button>
        </div>
    </x-slot>
</x-ui-modal>
