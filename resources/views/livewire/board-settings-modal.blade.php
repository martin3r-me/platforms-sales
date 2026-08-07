<x-nx-modal size="lg" model="modalShow">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-[var(--nx-accent)]/10 flex-shrink-0">
                @svg('heroicon-o-view-columns', 'w-5 h-5 text-[var(--nx-accent)]')
            </div>
            <div class="min-w-0">
                <h3 class="text-base font-semibold text-[var(--nx-text)] m-0 leading-tight">Board-Einstellungen</h3>
                <p class="text-[12px] text-[var(--nx-muted)] m-0 mt-0.5 truncate">{{ $board->name ?? '' }}</p>
            </div>
        </div>
    </x-slot>

    @if($board)
        <div class="space-y-6">

            {{-- Board Grunddaten --}}
            <div class="space-y-4">
                <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--nx-muted)] m-0">Grunddaten</h3>

                <div class="space-y-3">
                    <div>
                        <x-nx-input-text
                            name="board.name"
                            label="Board Name"
                            wire:model="board.name"
                            placeholder="z. B. Vertrieb Q1, Neukunden, etc."
                            :errorKey="'board.name'"
                        />
                    </div>

                    <div>
                        <x-nx-input-textarea
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
                            <x-nx-input-select
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
                <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--nx-muted)] m-0">Statistiken</h3>

                <x-nx-stat-grid cols="2">
                    <x-nx-stat
                        label="Gesamt Deals"
                        :value="$board->deals->count()"
                        icon="heroicon-o-rectangle-stack"
                        accent="var(--nx-info)"
                    />
                    <x-nx-stat
                        label="Gewonnene Deals"
                        :value="$board->deals->where('is_done', true)->count()"
                        icon="heroicon-o-check-circle"
                        accent="var(--nx-success)"
                    />
                </x-nx-stat-grid>
            </div>
        </div>
    @endif

    <x-slot name="footer">
        <div class="flex justify-between w-full">
            <x-nx-button variant="danger" wire:click="delete" wire:confirm="Wirklich löschen? Alle Deals in diesem Board werden in die INBOX verschoben.">
                Board löschen
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
