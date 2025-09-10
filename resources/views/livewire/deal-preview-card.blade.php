<x-ui-kanban-card 
    :sortable-id="$deal->id" 
    :title="'DEAL'"
    href="{{ route('sales.deals.show', $deal) }}"
>
    <div class="d-flex items-center gap-2 mb-2">
        @if($deal->isHighValue())
            <div class="w-6 h-6 bg-warning text-on-warning rounded-full d-flex items-center justify-center">
                <x-heroicon-o-star class="w-3 h-3"/>
            </div>
        @endif
        <span class="font-semibold">{{ $deal->title }}</span>
        @if($deal->salesBoard)<small class="text-secondary">| {{ $deal->salesBoard?->name }}</small>@endif
    </div> 
    <p class="text-xs text-muted">{{ $deal->description }}</p>

    <x-slot name="footer">
        <div class="d-flex justify-between items-center mb-2">
            @if($deal->deal_value)
                <span class="text-sm font-semibold text-green-600">
                    {{ number_format((float) $deal->deal_value, 0, ',', '.') }} €
                </span>
            @endif
            @if($deal->probability_percent)
                <div class="d-flex items-center gap-1">
                    @if($deal->probability_percent <= 30)
                        <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                        <span class="text-xs text-red-600">{{ $deal->probability_percent }}%</span>
                    @elseif($deal->probability_percent <= 70)
                        <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                        <span class="text-xs text-yellow-600">{{ $deal->probability_percent }}%</span>
                    @else
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                        <span class="text-xs text-green-600">{{ $deal->probability_percent }}%</span>
                    @endif
                </div>
            @endif
        </div>
        
        <div class="d-flex gap-1">
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
            <x-ui-badge variant="info" size="xs">
                {{ $deal->due_date->format('d.m.Y') }}
            </x-ui-badge>
            @endif
        </div>
    </x-slot>
</x-ui-kanban-card>