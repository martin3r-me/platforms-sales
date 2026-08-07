<x-nx-modal size="lg" wire:model="modalShow">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-[var(--nx-accent)]/10 flex-shrink-0">
                @svg('heroicon-o-link', 'w-5 h-5 text-[var(--nx-accent)]')
            </div>
            <div class="min-w-0 flex-1">
                <h3 class="text-base font-semibold text-[var(--nx-text)] m-0 leading-tight">CRM Verknüpfung</h3>
                <p class="text-[12px] text-[var(--nx-muted)] m-0 mt-0.5 truncate">{{ $deal?->title ?? 'Deal' }}</p>
            </div>
            @if($deal && ($deal->companies()->count() > 0 || $deal->contacts()->count() > 0))
                <x-nx-badge variant="success">
                    {{ $deal->companies()->count() + $deal->contacts()->count() }} Verknüpfung(en)
                </x-nx-badge>
            @endif
        </div>
    </x-slot>

    @if($deal)
        <div class="space-y-6">
            {{-- Info Box --}}
            <x-nx-callout variant="info" title="CRM Integration">
                Verknüpfe diesen Deal mit Companies und Contacts aus dem CRM für bessere Übersicht und Reporting.
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs mt-2">
                    <div class="flex items-center gap-1">
                        <span class="w-2 h-2 bg-[color:var(--nx-info)] rounded-full"></span>
                        <strong>Company:</strong> Firma/Unternehmen
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="w-2 h-2 bg-[color:var(--nx-success)] rounded-full"></span>
                        <strong>Contact:</strong> Ansprechpartner
                    </div>
                </div>
            </x-nx-callout>

            {{-- Company Selection --}}
            <div class="space-y-4">
                <h4 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--nx-muted)] m-0 flex items-center gap-2">
                    @svg('heroicon-o-building-office', 'w-3.5 h-3.5')
                    Company verknüpfen
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-nx-input-text
                            name="companySearch"
                            label="Company suchen"
                            wire:model.live.debounce.300ms="companySearch"
                            placeholder="Firma suchen..."
                        />
                    </div>
                    <div>
                        <x-nx-input-select
                            name="companyId"
                            label="Company auswählen"
                            :options="collect($companyOptions)->map(fn($o) => (object) $o)"
                            optionValue="value"
                            optionLabel="label"
                            :nullable="true"
                            nullLabel="– Keine Company –"
                            wire:model.live="companyId"
                        />
                    </div>
                </div>

                <div class="p-3 bg-[color:var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)]">
                    <div class="text-sm font-medium text-[var(--nx-text)] mb-1">Aktuelle Auswahl:</div>
                    <div class="text-sm text-[var(--nx-muted)]">{{ $companyDisplay ?? 'Keine Company ausgewählt' }}</div>
                </div>
            </div>

            {{-- Contact Selection --}}
            <div class="space-y-4">
                <h4 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--nx-muted)] m-0 flex items-center gap-2">
                    @svg('heroicon-o-user', 'w-3.5 h-3.5')
                    Contact verknüpfen
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-nx-input-text
                            name="contactSearch"
                            label="Contact suchen"
                            wire:model.live.debounce.300ms="contactSearch"
                            placeholder="Ansprechpartner suchen..."
                        />
                    </div>
                    <div>
                        <x-nx-input-select
                            name="contactId"
                            label="Contact auswählen"
                            :options="collect($contactOptions)->map(fn($o) => (object) $o)"
                            optionValue="value"
                            optionLabel="label"
                            :nullable="true"
                            nullLabel="– Kein Contact –"
                            wire:model.live="contactId"
                        />
                    </div>
                </div>

                <div class="p-3 bg-[color:var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)]">
                    <div class="text-sm font-medium text-[var(--nx-text)] mb-1">Aktuelle Auswahl:</div>
                    <div class="text-sm text-[var(--nx-muted)]">{{ $contactDisplay ?? 'Kein Contact ausgewählt' }}</div>
                </div>
            </div>

            {{-- Current Links Summary --}}
            @if($deal->companies()->count() > 0 || $deal->contacts()->count() > 0)
                <x-nx-callout variant="success" title="Aktuelle Verknüpfungen">
                    <div class="space-y-2 mt-2">
                        @foreach($deal->companies() as $company)
                            <div class="flex items-center justify-between p-2 bg-[color:var(--nx-surface)] rounded border border-[color:var(--nx-line)]">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-building-office', 'w-4 h-4 text-[color:var(--nx-success)]')
                                    <span class="text-sm font-medium text-[var(--nx-text)]">{{ $company->name }}</span>
                                </div>
                                <x-nx-badge variant="success">Company</x-nx-badge>
                            </div>
                        @endforeach
                        @foreach($deal->contacts() as $contact)
                            <div class="flex items-center justify-between p-2 bg-[color:var(--nx-surface)] rounded border border-[color:var(--nx-line)]">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-user', 'w-4 h-4 text-[color:var(--nx-success)]')
                                    <span class="text-sm font-medium text-[var(--nx-text)]">{{ $contact->display_name }}</span>
                                </div>
                                <x-nx-badge variant="info">Contact</x-nx-badge>
                            </div>
                        @endforeach
                    </div>
                </x-nx-callout>
            @endif
        </div>
    @endif

    <x-slot name="footer">
        <div class="flex items-center justify-between w-full">
            <x-nx-button variant="secondary" wire:click="closeModal">
                Abbrechen
            </x-nx-button>
            <x-nx-button variant="primary" wire:click="saveCompanyAndContact">
                @svg('heroicon-o-link', 'w-4 h-4')
                <span>Verknüpfungen speichern</span>
            </x-nx-button>
        </div>
    </x-slot>
</x-nx-modal>
