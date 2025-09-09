<x-ui-modal wire:model="modalShow">
    <x-slot name="title">Spalten-Einstellungen</x-slot>
    
    <x-slot name="content">
        <div class="space-y-4">
            <!-- Slot-Name -->
            <div>
                <x-ui-label for="slot.name">Name</x-ui-label>
                <x-ui-input 
                    id="slot.name"
                    wire:model="slot.name" 
                    placeholder="Spalten-Name eingeben"
                />
                @error('slot.name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Slot-Beschreibung -->
            <div>
                <x-ui-label for="slot.description">Beschreibung</x-ui-label>
                <x-ui-textarea 
                    id="slot.description"
                    wire:model="slot.description" 
                    placeholder="Spalten-Beschreibung eingeben"
                    rows="2"
                />
                @error('slot.description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Slot-Farbe -->
            <div>
                <x-ui-label for="slot.color">Farbe</x-ui-label>
                <x-ui-select wire:model="slot.color">
                    <option value="blue">Blau</option>
                    <option value="green">Grün</option>
                    <option value="yellow">Gelb</option>
                    <option value="red">Rot</option>
                    <option value="purple">Lila</option>
                    <option value="orange">Orange</option>
                    <option value="pink">Rosa</option>
                    <option value="indigo">Indigo</option>
                </x-ui-select>
                @error('slot.color') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
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