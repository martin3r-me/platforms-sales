<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Sales\Tools\Concerns\ResolvesSalesTeam;
use Platform\Sales\Models\SalesDeal;

class ListDealQuotationsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations, ResolvesSalesTeam;

    public function getName(): string
    {
        return 'sales.deal_quotations.GET';
    }

    public function getDescription(): string
    {
        return 'GET /sales/deal_quotations - Listet alle mit einem Deal verknüpften Lexoffice-Angebote auf. Zeigt Angebotsnummer, Status, Betrag und Datum. Nutze "integrations.lexware.quotation.GET" um Detaildaten eines Angebots direkt aus Lexoffice zu laden.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'deal_id' => [
                    'type' => 'integer',
                    'description' => 'Required: ID des Deals. Nutze "sales.deals.GET" um Deals zu finden.',
                ],
            ],
            'required' => ['deal_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $dealId = $arguments['deal_id'] ?? null;
            if (!$dealId) {
                return ToolResult::error('VALIDATION_ERROR', 'deal_id ist erforderlich.');
            }

            $deal = SalesDeal::find((int) $dealId);
            if (!$deal) {
                return ToolResult::error('NOT_FOUND', 'Deal nicht gefunden.');
            }

            $resolved = $this->resolveTeam(['team_id' => $deal->team_id], $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }

            $links = $deal->lexwareQuotations();

            $quotations = $links->map(function ($link) {
                return [
                    'id' => $link->id,
                    'quotation_external_id' => $link->quotation_external_id,
                    'quotation_number' => $link->quotation_number,
                    'voucher_status' => $link->voucher_status,
                    'status_label' => $link->status_label,
                    'voucher_date' => $link->voucher_date?->toDateString(),
                    'expiration_date' => $link->expiration_date?->toDateString(),
                    'total_amount' => (float) ($link->total_amount ?? 0),
                    'formatted_amount' => $link->formatted_amount,
                    'currency' => $link->currency,
                    'contact_name' => $link->contact_name,
                    'created_at' => $link->created_at?->toIso8601String(),
                ];
            });

            $totalAmount = $links->sum(fn ($l) => (float) ($l->total_amount ?? 0));

            return ToolResult::success([
                'deal_id' => $deal->id,
                'deal_title' => $deal->title,
                'quotations' => $quotations->toArray(),
                'summary' => [
                    'count' => $quotations->count(),
                    'total_amount' => $totalAmount,
                    'formatted_total' => number_format($totalAmount, 2, ',', '.') . ' €',
                ],
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['sales', 'deals', 'quotation', 'lexoffice', 'angebot', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'safe',
            'idempotent' => true,
            'confirmation_required' => false,
            'related_tools' => [
                'integrations.lexware.quotation.GET',
                'sales.deals.GET',
            ],
        ];
    }
}
