<x-ui-modal wire:model="modalShow">
    <x-slot name="title">Board-Einstellungen</x-slot>
    
    <x-slot name="content">
        <div class="space-y-4">
            <!-- Board-Name -->
            <div>
                <x-ui-input-text 
                    name="board.name"
                    label="Name"
                    wire:model="board.name" 
                    placeholder="Board-Name eingeben"
                    :errorKey="'board.name'"
                />
            </div>

            <!-- Board-Beschreibung -->
            <div>
                <x-ui-input-textarea 
                    name="board.description"
                    label="Beschreibung"
                    wire:model="board.description" 
                    placeholder="Board-Beschreibung eingeben"
                    rows="3"
                    :errorKey="'board.description'"
                />
            </div>

            <!-- Template-Auswahl -->
            @if($availableTemplates->count() > 0)
                <div>
                    <x-ui-input-select
                        name="board.sales_board_template_id"
                        label="Template"
                        :options="$availableTemplates"
                        optionValue="id"
                        optionLabel="name"
                        :nullable="true"
                        nullLabel="Kein Template"
                        wire:model="board.sales_board_template_id"
                    />
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