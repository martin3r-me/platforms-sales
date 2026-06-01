<?php

namespace Platform\Sales\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Platform\Core\Contracts\CashflowSignalDto;
use Platform\Core\Contracts\CashflowSignalProviderInterface;
use Platform\Sales\Models\SalesDeal;
use Platform\Sales\Models\SalesDealBillable;

class SalesPipelineSignalProvider implements CashflowSignalProviderInterface
{
    public function key(): string
    {
        return 'sales_pipeline';
    }

    public function label(): string
    {
        return 'Vertriebspipeline';
    }

    public function priority(): int
    {
        return 50;
    }

    public function signals(int $teamId, Carbon $from, Carbon $to): Collection
    {
        $deals = SalesDeal::forTeam($teamId)
            ->open()
            ->where('probability_percent', '>', 0)
            ->with(['activeBillables'])
            ->get();

        $signals = collect();

        foreach ($deals as $deal) {
            if ((float) $deal->deal_value <= 0) {
                continue;
            }

            $confidence = ($deal->probability_percent ?? 0) / 100;
            $confidenceLevel = $this->mapConfidenceLevel($deal->probability_percent ?? 0);
            $counterparty = $this->resolveCounterparty($deal);
            $baseDate = $deal->close_date ?? now()->addDays(30);
            $url = route('sales.deals.show', ['salesDeal' => $deal->uuid]);

            $activeBillables = $deal->activeBillables;

            if ($activeBillables->isEmpty()) {
                // No billables: single signal with deal_value
                if ($baseDate->between($from, $to)) {
                    $signals->push(new CashflowSignalDto(
                        providerKey: $this->key(),
                        externalId: "deal:{$deal->uuid}",
                        label: $deal->title,
                        direction: 'credit',
                        amount: (float) $deal->deal_value,
                        expectedDate: $baseDate->copy(),
                        confidence: $confidence,
                        confidenceLevel: $confidenceLevel,
                        counterparty: $counterparty,
                        url: $url,
                        meta: ['deal_uuid' => $deal->uuid],
                    ));
                }
            } else {
                // With billables: one signal per billable (or per recurring period)
                foreach ($activeBillables as $billable) {
                    if ((float) $billable->amount <= 0) {
                        continue;
                    }

                    $billableConfidence = ($billable->probability_percent ?? $deal->probability_percent ?? 0) / 100;
                    $billableConfidenceLevel = $this->mapConfidenceLevel($billable->probability_percent ?? $deal->probability_percent ?? 0);

                    // Billable start_date takes priority over deal close_date
                    $billableDate = $billable->start_date ?? $baseDate;

                    if ($billable->isOneTime()) {
                        if ($billableDate->between($from, $to)) {
                            $signals->push(new CashflowSignalDto(
                                providerKey: $this->key(),
                                externalId: "deal:{$deal->uuid}:billable:{$billable->uuid}",
                                label: $deal->title . ' – ' . $billable->name,
                                direction: 'credit',
                                amount: (float) $billable->amount,
                                expectedDate: $billableDate->copy(),
                                confidence: $billableConfidence,
                                confidenceLevel: $billableConfidenceLevel,
                                counterparty: $counterparty,
                                url: $url,
                                meta: [
                                    'deal_uuid' => $deal->uuid,
                                    'billable_uuid' => $billable->uuid,
                                ],
                            ));
                        }
                    } elseif ($billable->isRecurring()) {
                        $this->generateRecurringSignals(
                            $signals, $deal, $billable, $billableDate, $from, $to,
                            $billableConfidence, $billableConfidenceLevel, $counterparty, $url
                        );
                    }
                }
            }
        }

        return $signals;
    }

    public function isResolved(int $teamId, string $externalId): ?bool
    {
        $dealUuid = $this->parseDealUuid($externalId);

        if (! $dealUuid) {
            return null;
        }

        $deal = SalesDeal::forTeam($teamId)->where('uuid', $dealUuid)->first();

        if (! $deal) {
            return null;
        }

        return $deal->isWon() || $deal->isLost();
    }

    protected function generateRecurringSignals(
        Collection $signals,
        SalesDeal $deal,
        SalesDealBillable $billable,
        Carbon $baseDate,
        Carbon $from,
        Carbon $to,
        float $confidence,
        string $confidenceLevel,
        ?string $counterparty,
        string $url,
    ): void {
        $intervalMonths = match ($billable->billing_interval) {
            'quarterly' => 3,
            'yearly' => 12,
            default => 1, // monthly
        };

        $cursor = $baseDate->copy();
        $periodIndex = 0;
        $maxPeriods = $billable->duration_months
            ? (int) ceil($billable->duration_months / $intervalMonths)
            : null;

        // end_date caps the recurring series
        $endDate = $billable->end_date ? $billable->end_date->copy() : $to;
        $effectiveEnd = $endDate->lt($to) ? $endDate : $to;

        while ($cursor->lte($effectiveEnd)) {
            if ($maxPeriods !== null && $periodIndex >= $maxPeriods) {
                break;
            }

            if ($cursor->gte($from)) {
                $signals->push(new CashflowSignalDto(
                    providerKey: $this->key(),
                    externalId: "deal:{$deal->uuid}:billable:{$billable->uuid}:{$periodIndex}",
                    label: $deal->title . ' – ' . $billable->name,
                    direction: 'credit',
                    amount: (float) $billable->amount,
                    expectedDate: $cursor->copy(),
                    confidence: $confidence,
                    confidenceLevel: $confidenceLevel,
                    counterparty: $counterparty,
                    url: $url,
                    meta: [
                        'deal_uuid' => $deal->uuid,
                        'billable_uuid' => $billable->uuid,
                        'slot_name' => $billable->name,
                    ],
                ));
            }

            $cursor->addMonths($intervalMonths);
            $periodIndex++;
        }
    }

    protected function mapConfidenceLevel(int $probabilityPercent): string
    {
        return match (true) {
            $probabilityPercent >= 80 => 'confirmed',
            $probabilityPercent >= 50 => 'expected',
            default => 'speculative',
        };
    }

    protected function resolveCounterparty(SalesDeal $deal): ?string
    {
        $companies = $deal->companies();

        if ($companies->isNotEmpty()) {
            return $companies->first()->name ?? null;
        }

        return $deal->title;
    }

    protected function parseDealUuid(string $externalId): ?string
    {
        // Formats: "deal:{uuid}" or "deal:{uuid}:billable:{uuid}" or "deal:{uuid}:billable:{uuid}:{index}"
        if (Str::startsWith($externalId, 'deal:')) {
            $parts = explode(':', $externalId);

            return $parts[1] ?? null;
        }

        return null;
    }
}
