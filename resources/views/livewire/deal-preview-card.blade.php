@props(['deal'])
@php
    $isDone = $deal->is_done ?? false;
    $isHot = $deal->isHot();
    $isOverdue = $deal->due_date && $deal->due_date->isPast() && !$isDone;

    // Primärwert: Deal-Wert, sonst Summe der einmaligen Billables
    $hasBillables = $deal->hasBillables();
    $rcArr = 0;
    if ($hasBillables) {
        $rcArr = $deal->billables->filter(fn($b) => $b->isRecurring())->sum(function($b) {
            return match($b->billing_interval) {
                'monthly'   => (float) $b->amount * 12,
                'quarterly' => (float) $b->amount * 4,
                'yearly'    => (float) $b->amount,
                default     => (float) $b->amount * 12,
            };
        });
    }
    $primaryValue = (float) ($deal->deal_value ?: ($hasBillables ? $deal->billables->filter(fn($b) => $b->isOneTime())->sum('total_value') : 0));

    // Linke Edge: Farbe nach Status (Notion-flach)
    $edgeColor = match (true) {
        $isOverdue => 'var(--nx-danger)',
        $isDone    => 'var(--nx-success)',
        default    => 'var(--nx-line-strong)',
    };

    // Verantwortlicher
    $userInCharge = $deal->userInCharge ?? null;
    $initials = $userInCharge ? mb_strtoupper(mb_substr($userInCharge->name ?? $userInCharge->email ?? 'U', 0, 1)) : null;

    // Abnehmer (verknüpfte CRM-Company) – eager geladen über companyLinks.company
    $abnehmerCompany = optional($deal->companyLinks->first())->company;
@endphp
<x-nx-kanban-card
    :title="''"
    :sortable-id="$deal->id"
    :href="route('sales.deals.show', $deal)"
    class="group/card relative {{ $isDone ? 'opacity-60' : '' }}"
>
    {{-- Vertikales Color-Band links (Status) --}}
    <div
        class="absolute top-2.5 bottom-2.5 left-1.5 w-[3px] rounded-full"
        style="background-color: {{ $edgeColor }};"
    ></div>

    <div class="pl-3">
        {{-- Titel + Signale --}}
        <div class="flex items-start gap-1.5">
            <h4 class="flex-1 min-w-0 text-sm font-medium text-[color:var(--nx-text)] m-0 {{ $isDone ? 'line-through text-[color:var(--nx-muted)]' : '' }}">
                {{ $deal->title }}
            </h4>
            @if($isHot)
                <span title="Hot" class="mt-0.5 w-1.5 h-1.5 rounded-full bg-[color:var(--nx-danger)] shrink-0"></span>
            @endif
            @if($deal->is_starred)
                @svg('heroicon-s-star', 'w-3.5 h-3.5 mt-0.5 text-[color:var(--nx-warning)] shrink-0')
            @endif
        </div>

        {{-- Abnehmer (verknüpfte Company) --}}
        @if($abnehmerCompany)
            <div class="mt-1 inline-flex items-center gap-1 text-xs text-[color:var(--nx-muted)] min-w-0">
                @svg('heroicon-o-building-office-2', 'w-3 h-3 shrink-0')
                <span class="truncate">{{ $abnehmerCompany->name }}</span>
            </div>
        @endif

        {{-- Wert + Wahrscheinlichkeit --}}
        @if($primaryValue > 0 || $deal->probability_percent)
            <div class="mt-2 flex items-baseline justify-between gap-2">
                @if($primaryValue > 0)
                    <span class="text-base font-semibold text-[color:var(--nx-text)] tabular-nums">
                        {{ number_format($primaryValue, 0, ',', '.') }} €
                    </span>
                @else
                    <span></span>
                @endif
                @if($deal->probability_percent)
                    @php $probVar = $deal->probability_percent <= 30 ? 'danger' : ($deal->probability_percent <= 70 ? 'warning' : 'success'); @endphp
                    <span class="inline-flex items-center gap-1 text-xs text-[color:var(--nx-muted)] tabular-nums shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-[color:var(--nx-{{ $probVar }})]"></span>
                        {{ $deal->probability_percent }}%
                    </span>
                @endif
            </div>
        @endif

        {{-- Wiederkehrend (leise) --}}
        @if($rcArr > 0)
            <div class="mt-1 inline-flex items-center gap-1 text-xs text-[color:var(--nx-muted)] tabular-nums">
                @svg('heroicon-o-arrow-path', 'w-3 h-3')
                <span>+ {{ number_format($rcArr, 0, ',', '.') }} € / Jahr</span>
            </div>
        @endif

        {{-- Fußzeile: Verantwortlicher + Fälligkeit --}}
        @if($userInCharge || $deal->due_date)
            <div class="mt-2.5 pt-2 border-t border-[color:var(--nx-line)] flex items-center justify-between gap-2">
                @if($userInCharge)
                    <span class="inline-flex items-center gap-1.5 min-w-0 text-xs text-[color:var(--nx-muted)]">
                        @if($userInCharge->avatar)
                            <img src="{{ method_exists($userInCharge, 'avatarUrl') ? $userInCharge->avatarUrl() : $userInCharge->avatar }}" loading="lazy" alt="" class="w-4 h-4 rounded-full object-cover shrink-0">
                        @else
                            <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-[color:var(--nx-bg)] border border-[color:var(--nx-line)] text-[10px] shrink-0">{{ $initials }}</span>
                        @endif
                        <span class="truncate">{{ $userInCharge->name ?? $userInCharge->email }}</span>
                    </span>
                @else
                    <span></span>
                @endif
                @if($deal->due_date)
                    @php $dateVar = $isOverdue ? 'danger' : ($deal->due_date->isToday() ? 'warning' : 'muted'); @endphp
                    <span class="inline-flex items-center gap-1 text-xs text-[color:var(--nx-{{ $dateVar }})] shrink-0">
                        @svg('heroicon-o-calendar', 'w-3 h-3')
                        <span>{{ $deal->due_date->format('d.m.') }}</span>
                    </span>
                @endif
            </div>
        @endif
    </div>
</x-nx-kanban-card>
