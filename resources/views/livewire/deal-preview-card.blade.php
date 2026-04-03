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
        $rcTotal = $rcBillables->sum('total_value');
    }
@endphp
<x-ui-kanban-card
    :title="''"
    :sortable-id="$deal->id"
    :href="route('sales.deals.show', $deal)"
>
    <!-- Hot / Starred Indikatoren -->
    @if($isHot || $deal->is_starred)
        <div class="mb-2 flex items-center gap-1.5 flex-wrap">
            @if($isHot)
                <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium rounded bg-[var(--ui-danger-5)] text-[var(--ui-danger)] border border-[var(--ui-danger)]/30">
                    Hot
                </span>
            @endif
            @if($deal->is_starred)
                <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium rounded bg-[var(--ui-warning-5)] text-[var(--ui-warning)] border border-[var(--ui-warning)]/30">
                    @svg('heroicon-o-star', 'w-2.5 h-2.5')
                </span>
            @endif
            @if($isHighValue)
                <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium rounded bg-[var(--ui-success-5)] text-[var(--ui-success)] border border-[var(--ui-success)]/30">
                    High Value
                </span>
            @endif
        </div>
    @endif

    <!-- Titel -->
    <div class="mb-2">
        <h4 class="text-sm font-medium text-[var(--ui-secondary)] m-0 {{ $isDone ? 'line-through text-[var(--ui-muted)]' : '' }}">
            {{ $deal->title }}
        </h4>
    </div>

    <!-- Verantwortlicher -->
    @php
        $userInCharge = $deal->userInCharge ?? null;
        $initials = $userInCharge ? mb_strtoupper(mb_substr($userInCharge->name ?? $userInCharge->email ?? 'U', 0, 1)) : null;
    @endphp
    @if($userInCharge)
        <div class="mb-3">
            <span class="inline-flex items-start gap-1 text-xs text-[var(--ui-muted)] min-w-0">
                @if($userInCharge->avatar)
                    <img src="{{ $userInCharge->avatar }}" alt="{{ $userInCharge->name ?? $userInCharge->email }}" class="w-3.5 h-3.5 rounded-full object-cover mt-0.5">
                @else
                    <span class="inline-flex items-center justify-center w-3.5 h-3.5 rounded-full bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 text-[10px] text-[var(--ui-muted)] mt-0.5">{{ $initials }}</span>
                @endif
                <span class="truncate max-w-[7rem]">{{ $userInCharge->name ?? $userInCharge->email }}</span>
            </span>
        </div>
    @endif

    <!-- Deal-Wert: Gesamtwert + Wahrscheinlichkeit -->
    @if($deal->deal_value || $deal->probability_percent)
        <div class="mb-2 flex items-center justify-between gap-2">
            @if($deal->deal_value)
                <span class="text-sm font-bold text-[var(--ui-success)]">
                    {{ number_format((float) $deal->deal_value, 0, ',', '.') }} €
                </span>
            @else
                <span></span>
            @endif
            @if($deal->probability_percent)
                @php
                    $probVariant = $deal->probability_percent <= 30 ? 'danger' : ($deal->probability_percent <= 70 ? 'warning' : 'success');
                @endphp
                <span class="inline-flex items-center gap-1 text-xs font-semibold text-[var(--ui-{{ $probVariant }})]">
                    <span class="w-2 h-2 bg-[var(--ui-{{ $probVariant }})] rounded-full"></span>
                    {{ $deal->probability_percent }}%
                </span>
            @endif
        </div>
    @endif

    <!-- Einmalig / Wiederkehrend Aufspaltung -->
    @if($hasBillables && ($otTotal > 0 || $rcTotal > 0))
        <div class="grid grid-cols-2 gap-1.5 mb-2">
            @if($otTotal > 0)
                <div class="px-2 py-1.5 rounded bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/30">
                    <div class="text-[10px] text-[var(--ui-muted)] leading-tight">Einmalig</div>
                    <div class="text-xs font-bold text-[var(--ui-secondary)]">{{ number_format((float) $otTotal, 0, ',', '.') }} €</div>
                </div>
            @endif
            @if($rcTotal > 0)
                <div class="px-2 py-1.5 rounded bg-[var(--ui-primary-5)] border border-[var(--ui-primary)]/20">
                    <div class="text-[10px] text-[var(--ui-primary)] leading-tight">Wiederkehrend</div>
                    <div class="text-xs font-bold text-[var(--ui-primary)]">{{ number_format((float) $rcTotal, 0, ',', '.') }} €</div>
                </div>
            @endif
        </div>
    @endif

    <!-- Fälligkeitsdatum -->
    @if($deal->due_date)
        <div class="mb-2">
            @php
                $isOverdue = $deal->due_date->isPast() && !$isDone;
                $isToday = $deal->due_date->isToday();
                $dateVariant = $isOverdue ? 'danger' : ($isToday ? 'warning' : 'muted');
            @endphp
            <span class="inline-flex items-start gap-1 text-xs text-[var(--ui-{{ $dateVariant }})]">
                @svg('heroicon-o-calendar', 'w-3 h-3 mt-0.5')
                <span>{{ $deal->due_date->format('d.m.Y') }}</span>
            </span>
        </div>
    @endif

    <!-- Quelle / Typ -->
    @if($deal->deal_source || $deal->deal_type)
        <div class="flex gap-1 flex-wrap">
            @if($deal->deal_source)
                <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium rounded bg-[var(--ui-muted-5)] text-[var(--ui-muted)] border border-[var(--ui-border)]/30">
                    {{ $deal->deal_source }}
                </span>
            @endif
            @if($deal->deal_type)
                <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium rounded bg-[var(--ui-muted-5)] text-[var(--ui-muted)] border border-[var(--ui-border)]/30">
                    {{ $deal->deal_type }}
                </span>
            @endif
        </div>
    @endif
</x-ui-kanban-card>
