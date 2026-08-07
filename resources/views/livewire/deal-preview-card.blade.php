@props(['deal'])
@php
    $isDone = $deal->is_done ?? false;
    $isHot = $deal->isHot();
    $isHighValue = $deal->isHighValue();
    $hasBillables = $deal->hasBillables();

    if ($hasBillables) {
        $otBillables = $deal->billables->filter(fn($b) => $b->isOneTime());
        $rcBillables = $deal->billables->filter(fn($b) => $b->isRecurring());
        $otTotal = $otBillables->sum('total_value');
        // ARR: auf Jahreswert normalisieren
        $rcArr = $rcBillables->sum(function($b) {
            return match($b->billing_interval) {
                'monthly' => (float) $b->amount * 12,
                'quarterly' => (float) $b->amount * 4,
                'yearly' => (float) $b->amount,
                default => (float) $b->amount * 12,
            };
        });
    }

    $isOverdue = $deal->due_date && $deal->due_date->isPast() && !$isDone;

    // Linke Edge: Farbe nach Status (Notion-flach)
    $edgeColor = match (true) {
        $isOverdue => 'var(--nx-danger)',
        $isDone    => 'var(--nx-success)',
        default    => 'var(--nx-line-strong)',
    };

    // Card bleibt immer weiß. Done signalisiert sich nur über Opazität.
    $surface = $isDone ? 'opacity-60' : '';
@endphp
<x-nx-kanban-card
    :title="''"
    :sortable-id="$deal->id"
    :href="route('sales.deals.show', $deal)"
    class="group/card relative {{ $surface }}"
>
    {{-- Vertikales Color-Band links (Status) --}}
    <div
        class="absolute top-2.5 bottom-2.5 left-1.5 w-[3px] rounded-full"
        style="background-color: {{ $edgeColor }};"
    ></div>

    {{-- Hot / Starred Indikatoren --}}
    @if($isHot || $deal->is_starred)
        <div class="mb-2 flex items-center gap-1.5 flex-wrap pl-3">
            @if($isHot)
                <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium rounded bg-[rgba(224,49,49,.09)] text-[color:var(--nx-danger)] border border-[rgba(224,49,49,.30)]">
                    Hot
                </span>
            @endif
            @if($deal->is_starred)
                <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium rounded bg-[rgba(232,89,12,.09)] text-[color:var(--nx-warning)] border border-[rgba(232,89,12,.30)]">
                    @svg('heroicon-o-star', 'w-2.5 h-2.5')
                </span>
            @endif
            @if($isHighValue)
                <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium rounded bg-[rgba(47,158,68,.09)] text-[color:var(--nx-success)] border border-[rgba(47,158,68,.30)]">
                    High Value
                </span>
            @endif
        </div>
    @endif

    {{-- Titel --}}
    <div class="mb-2 pl-3">
        <h4 class="text-sm font-medium text-[color:var(--nx-text)] m-0 {{ $isDone ? 'line-through text-[color:var(--nx-muted)]' : '' }}">
            {{ $deal->title }}
        </h4>
    </div>

    {{-- Verantwortlicher --}}
    @php
        $userInCharge = $deal->userInCharge ?? null;
        $initials = $userInCharge ? mb_strtoupper(mb_substr($userInCharge->name ?? $userInCharge->email ?? 'U', 0, 1)) : null;
    @endphp
    @if($userInCharge)
        <div class="mb-3 pl-3">
            <span class="inline-flex items-start gap-1 text-xs text-[color:var(--nx-muted)] min-w-0">
                @if($userInCharge->avatar)
                    <img src="{{ $userInCharge->avatar }}" alt="{{ $userInCharge->name ?? $userInCharge->email }}" class="w-3.5 h-3.5 rounded-full object-cover mt-0.5">
                @else
                    <span class="inline-flex items-center justify-center w-3.5 h-3.5 rounded-full bg-[color:var(--nx-bg)] border border-[color:var(--nx-line)] text-[10px] text-[color:var(--nx-muted)] mt-0.5">{{ $initials }}</span>
                @endif
                <span class="truncate max-w-[7rem]">{{ $userInCharge->name ?? $userInCharge->email }}</span>
            </span>
        </div>
    @endif

    {{-- Deal-Wert: Gesamtwert + Wahrscheinlichkeit --}}
    @if($deal->deal_value || $deal->probability_percent)
        <div class="mb-2 flex items-center justify-between gap-2 pl-3">
            @if($deal->deal_value)
                <span class="text-sm font-bold text-[color:var(--nx-success)]">
                    {{ number_format((float) $deal->deal_value, 0, ',', '.') }} €
                </span>
            @else
                <span></span>
            @endif
            @if($deal->probability_percent)
                @php
                    $probVar = $deal->probability_percent <= 30 ? 'danger' : ($deal->probability_percent <= 70 ? 'warning' : 'success');
                @endphp
                <span class="inline-flex items-center gap-1 text-xs font-semibold text-[color:var(--nx-{{ $probVar }})]">
                    <span class="w-2 h-2 bg-[color:var(--nx-{{ $probVar }})] rounded-full"></span>
                    {{ $deal->probability_percent }}%
                </span>
            @endif
        </div>
    @endif

    {{-- Einmalig / Wiederkehrend Aufspaltung --}}
    @if($hasBillables && ($otTotal > 0 || $rcArr > 0))
        <div class="grid grid-cols-2 gap-1.5 mb-2 pl-3">
            @if($otTotal > 0)
                <div class="px-2 py-1.5 rounded bg-[color:var(--nx-bg)] border border-[color:var(--nx-line)]">
                    <div class="flex items-center gap-1 text-[10px] text-[color:var(--nx-muted)] leading-tight">
                        @svg('heroicon-o-banknotes', 'w-2.5 h-2.5')
                        <span>Einmalig</span>
                    </div>
                    <div class="text-xs font-bold text-[color:var(--nx-text)]">{{ number_format((float) $otTotal, 0, ',', '.') }} €</div>
                </div>
            @endif
            @if($rcArr > 0)
                <div class="px-2 py-1.5 rounded bg-[rgba(25,113,194,.09)] border border-[rgba(25,113,194,.20)]">
                    <div class="flex items-center gap-1 text-[10px] text-[color:var(--nx-info)] leading-tight">
                        @svg('heroicon-o-arrow-path', 'w-2.5 h-2.5')
                        <span>/Jahr</span>
                    </div>
                    <div class="text-xs font-bold text-[color:var(--nx-info)]">{{ number_format((float) $rcArr, 0, ',', '.') }} €</div>
                </div>
            @endif
        </div>
    @endif

    {{-- Fälligkeitsdatum --}}
    @if($deal->due_date)
        <div class="mb-2 pl-3">
            @php
                $isToday = $deal->due_date->isToday();
                $dateVar = $isOverdue ? 'danger' : ($isToday ? 'warning' : 'muted');
            @endphp
            <span class="inline-flex items-start gap-1 text-xs text-[color:var(--nx-{{ $dateVar }})]">
                @svg('heroicon-o-calendar', 'w-3 h-3 mt-0.5')
                <span>{{ $deal->due_date->format('d.m.Y') }}</span>
            </span>
        </div>
    @endif

    {{-- Quelle / Typ --}}
    @if($deal->deal_source || $deal->deal_type)
        <div class="flex gap-1 flex-wrap pl-3">
            @if($deal->deal_source)
                <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium rounded bg-[color:var(--nx-bg)] text-[color:var(--nx-muted)] border border-[color:var(--nx-line)]">
                    {{ $deal->deal_source }}
                </span>
            @endif
            @if($deal->deal_type)
                <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium rounded bg-[color:var(--nx-bg)] text-[color:var(--nx-muted)] border border-[color:var(--nx-line)]">
                    {{ $deal->deal_type }}
                </span>
            @endif
        </div>
    @endif
</x-nx-kanban-card>
