<x-ui-modal size="lg" wire:model="modalShow">
    <x-slot name="header">
        Board-Einstellungen: {{ $board->name ?? '' }}
    </x-slot>

    @if($board)
        <div class="flex-grow-1 overflow-y-auto p-4 space-y-6">

            {{-- Board Grunddaten --}}
            <div class="space-y-4">
                <h3 class="text-lg font-medium text-[var(--ui-secondary)]">Grunddaten</h3>
                
                <div class="space-y-3">
                    <div>
                        <x-ui-input-text 
                            name="board.name"
                            label="Board Name"
                            wire:model="board.name" 
                            placeholder="z. B. Vertrieb Q1, Neukunden, etc."
                            :errorKey="'board.name'"
                        />
                    </div>

                    <div>
                        <x-ui-input-textarea 
                            name="board.description"
                            label="Beschreibung"
                            wire:model="board.description" 
                            placeholder="Beschreibung des Sales Boards..."
                            rows="3"
                            :errorKey="'board.description'"
                        />
                    </div>

                    <!-- Template-Auswahl -->
                    @if($availableTemplates && $availableTemplates->count() > 0)
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
            </div>

            {{-- Board Statistiken --}}
            <div class="space-y-4">
                <h3 class="text-lg font-medium text-[var(--ui-secondary)]">Statistiken</h3>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 bg-[var(--ui-primary-5)] border border-[var(--ui-primary)]/30 rounded-lg">
                        <div class="text-sm text-[var(--ui-primary)]">Gesamt Deals</div>
                        <div class="text-2xl font-bold text-[var(--ui-primary)]">{{ $board->deals->count() }}</div>
                    </div>
                    <div class="p-4 bg-[var(--ui-success-5)] border border-[var(--ui-success)]/30 rounded-lg">
                        <div class="text-sm text-[var(--ui-success)]">Gewonnene Deals</div>
                        <div class="text-2xl font-bold text-[var(--ui-success)]">{{ $board->deals->where('is_done', true)->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <x-slot name="footer">
        <div class="flex justify-between">
            <x-ui-confirm-button 
                action="delete" 
                text="Board löschen" 
                confirmText="Wirklich löschen? Alle Deals in diesem Board werden in die INBOX verschoben." 
                variant="danger-outline"
            />
            <div class="flex gap-2">
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