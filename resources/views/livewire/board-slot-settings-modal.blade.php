<x-nx-modal size="md" model="modalShow">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-[var(--nx-accent)]/10 flex-shrink-0">
                @svg('heroicon-o-bars-4', 'w-5 h-5 text-[var(--nx-accent)]')
            </div>
            <div class="min-w-0">
                <h3 class="text-base font-semibold text-[var(--nx-text)] m-0 leading-tight">Spalten-Einstellungen</h3>
                <p class="text-[12px] text-[var(--nx-muted)] m-0 mt-0.5">Spalte bearbeiten und verwalten</p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-4">
        <!-- Slot-Name -->
        <x-nx-input-text
            name="slot.name"
            label="Name"
            wire:model="slot.name"
            placeholder="Spalten-Name eingeben"
            :errorKey="'slot.name'"
        />

        <!-- Slot-Beschreibung -->
        <x-nx-input-textarea
            name="slot.description"
            label="Beschreibung"
            wire:model="slot.description"
            placeholder="Spalten-Beschreibung eingeben"
            rows="2"
            :errorKey="'slot.description'"
        />

        <!-- Slot-Farbe -->
        <x-nx-input-select
            name="slot.color"
            label="Farbe"
            :options="collect([
                (object)['value' => 'blue', 'label' => 'Blau'],
                (object)['value' => 'green', 'label' => 'Grün'],
                (object)['value' => 'yellow', 'label' => 'Gelb'],
                (object)['value' => 'red', 'label' => 'Rot'],
                (object)['value' => 'purple', 'label' => 'Lila'],
                (object)['value' => 'orange', 'label' => 'Orange'],
                (object)['value' => 'pink', 'label' => 'Rosa'],
                (object)['value' => 'indigo', 'label' => 'Indigo']
            ])"
            optionValue="value"
            optionLabel="label"
            wire:model="slot.color"
            :errorKey="'slot.color'"
        />
    </div>

    <x-slot name="footer">
        <div class="flex justify-between items-center w-full">
            <x-nx-button variant="danger" wire:click="delete">
                Löschen
            </x-nx-button>
            <div class="flex gap-2">
                <x-nx-button variant="secondary" wire:click="closeModal">
                    Abbrechen
                </x-nx-button>
                <x-nx-button variant="primary" wire:click="save">
                    Speichern
                </x-nx-button>
            </div>
        </div>
    </x-slot>
</x-nx-modal>
