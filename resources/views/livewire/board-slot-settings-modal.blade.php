<x-ui-modal wire:model="modalShow">
    <x-slot name="title">Spalten-Einstellungen</x-slot>
    
    <x-slot name="content">
        <div class="space-y-4">
            <!-- Slot-Name -->
            <div>
                <x-ui-input-text 
                    name="slot.name"
                    label="Name"
                    wire:model="slot.name" 
                    placeholder="Spalten-Name eingeben"
                    :errorKey="'slot.name'"
                />
            </div>

            <!-- Slot-Beschreibung -->
            <div>
                <x-ui-input-textarea 
                    name="slot.description"
                    label="Beschreibung"
                    wire:model="slot.description" 
                    placeholder="Spalten-Beschreibung eingeben"
                    rows="2"
                    :errorKey="'slot.description'"
                />
            </div>

            <!-- Slot-Farbe -->
            <div>
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
        </div>
    </x-slot>

    <x-slot name="footer">
        <div class="d-flex justify-between">
            <x-ui-button variant="danger" wire:click="delete">
                Löschen
            </x-ui-button>
            <div class="d-flex gap-2">
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