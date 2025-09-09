<x-ui-modal wire:model="modalShow">
    <x-slot name="title">Board-Einstellungen</x-slot>
    
    <x-slot name="content">
        <div class="space-y-4">
            <!-- Board-Name -->
            <div>
                <x-ui-label for="board.name">Name</x-ui-label>
                <x-ui-input 
                    id="board.name"
                    wire:model="board.name" 
                    placeholder="Board-Name eingeben"
                />
                @error('board.name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Board-Beschreibung -->
            <div>
                <x-ui-label for="board.description">Beschreibung</x-ui-label>
                <x-ui-textarea 
                    id="board.description"
                    wire:model="board.description" 
                    placeholder="Board-Beschreibung eingeben"
                    rows="3"
                />
                @error('board.description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Template-Auswahl -->
            @if($availableTemplates->count() > 0)
                <div>
                    <x-ui-label for="board.template">Template</x-ui-label>
                    <x-ui-select wire:model="board.sales_board_template_id">
                        <option value="">Kein Template</option>
                        @foreach($availableTemplates as $template)
                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                        @endforeach
                    </x-ui-select>
                </div>
            @endif
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