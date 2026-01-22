<x-ui-kanban-card 
    :sortable-id="$deal->id" 
    :title="'DEAL'"
    href="{{ route('sales.deals.show', $deal) }}"
>
    <div class="flex items-center gap-2 mb-2">
        @if($deal->isHighValue())
            <div class="w-6 h-6 bg-[var(--ui-warning)] text-[var(--ui-on-warning)] rounded-full flex items-center justify-center">
                @svg('heroicon-o-star', 'w-3 h-3')
            </div>
        @endif
        <span class="font-semibold text-[var(--ui-secondary)]">{{ $deal->title }}</span>
        @if($deal->salesBoard)<small class="text-[var(--ui-muted)]">| {{ $deal->salesBoard?->name }}</small>@endif
    </div> 
    <p class="text-xs text-[var(--ui-muted)]">{{ $deal->description }}</p>

    <x-slot name="footer">
        <div class="flex justify-between items-center mb-2">
            @if($deal->deal_value)
                <span class="text-sm font-semibold text-[var(--ui-success)]">
                    {{ number_format((float) $deal->deal_value, 0, ',', '.') }} €
                </span>
            @endif
            @if($deal->probability_percent)
                <div class="flex items-center gap-1">
                    @if($deal->probability_percent <= 30)
                        <div class="w-2 h-2 bg-[var(--ui-danger)] rounded-full"></div>
                        <span class="text-xs text-[var(--ui-danger)]">{{ $deal->probability_percent }}%</span>
                    @elseif($deal->probability_percent <= 70)
                        <div class="w-2 h-2 bg-[var(--ui-warning)] rounded-full"></div>
                        <span class="text-xs text-[var(--ui-warning)]">{{ $deal->probability_percent }}%</span>
                    @else
                        <div class="w-2 h-2 bg-[var(--ui-success)] rounded-full"></div>
                        <span class="text-xs text-[var(--ui-success)]">{{ $deal->probability_percent }}%</span>
                    @endif
                </div>
            @endif
        </div>
        
        <div class="flex gap-1">
            @if($deal->deal_source)
            <x-ui-badge variant="secondary" size="xs">
                {{ $deal->deal_source }}
            </x-ui-badge>
            @endif
            @if($deal->deal_type)
            <x-ui-badge variant="secondary" size="xs">
                {{ $deal->deal_type }}
            </x-ui-badge>
            @endif
            @if($deal->due_date)
            <x-ui-badge variant="primary" size="xs">
                {{ $deal->due_date->format('d.m.Y') }}
            </x-ui-badge>
            @endif
        </div>
    </x-slot>
</x-ui-kanban-card>