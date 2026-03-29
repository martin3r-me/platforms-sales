<x-ui-modal size="md" model="modalShow">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-[var(--ui-success-5)] rounded-lg flex items-center justify-center">
                    @svg('heroicon-o-bars-4', 'w-4 h-4 text-[var(--ui-success)]')
                </div>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-[var(--ui-secondary)]">Spalten-Einstellungen</h3>
                <p class="text-sm text-[var(--ui-muted)]">Spalte bearbeiten und verwalten</p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Slot-Name -->
        <x-ui-input-text
            name="slot.name"
            label="Name"
            wire:model="slot.name"
            placeholder="Spalten-Name eingeben"
            :errorKey="'slot.name'"
        />

        <!-- Slot-Beschreibung -->
        <x-ui-input-textarea
            name="slot.description"
            label="Beschreibung"
            wire:model="slot.description"
            placeholder="Spalten-Beschreibung eingeben"
            rows="2"
            :errorKey="'slot.description'"
        />

        <!-- Slot-Farbe -->
        <x-ui-input-select
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
        <div class="flex justify-between items-center">
            <x-ui-button variant="danger" wire:click="delete">
                Löschen
            </x-ui-button>
            <div class="flex gap-3">
                <x-ui-button variant="secondary" wire:click="closeModal">
                    Abbrechen
                </x-ui-button>
                <x-ui-button variant="primary" wire:click="save">
                    Speichern
                </x-ui-button>
            </div>
        </div>
    </x-slot>
</x-ui-modal>
