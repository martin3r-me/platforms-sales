<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Sales\Tools\Concerns\ResolvesSalesTeam;
use Platform\Sales\Models\SalesDeal;

class LinkQuotationTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations, ResolvesSalesTeam;

    public function getName(): string
    {
        return 'sales.deal_quotations.POST';
    }

    public function getDescription(): string
    {
        return 'POST /sales/deal_quotations - Verknüpft ein Lexoffice-Angebot (Quotation) mit einem Deal. Nutze zuerst "integrations.lexware.quotations.POST" um ein Angebot zu erstellen, oder "integrations.lexware.quotations.GET" um bestehende Angebote zu finden. Dann diese Verknüpfung erstellen.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'deal_id' => [
                    'type' => 'integer',
                    'description' => 'Required: ID des Sales-Deals. Nutze "sales.deals.GET" um Deals zu finden.',
                ],
                'quotation_external_id' => [
                    'type' => 'string',
                    'description' => 'Required: UUID des Lexoffice-Angebots. Kommt aus "integrations.lexware.quotations.POST" oder "integrations.lexware.quotation.GET".',
                ],
                'quotation_number' => [
                    'type' => 'string',
                    'description' => 'Optional: Angebotsnummer (z.B. "AG-2025-001"). Wird automatisch aus Lexoffice übernommen wenn verfügbar.',
                ],
                'voucher_status' => [
                    'type' => 'string',
                    'description' => 'Optional: Status des Angebots. Erlaubte Werte: draft, open, accepted, rejected.',
                ],
                'voucher_date' => [
                    'type' => 'string',
                    'description' => 'Optional: Angebotsdatum (YYYY-MM-DD).',
                ],
                'expiration_date' => [
                    'type' => 'string',
                    'description' => 'Optional: Ablaufdatum (YYYY-MM-DD).',
                ],
                'total_amount' => [
                    'type' => 'number',
                    'description' => 'Optional: Gesamtbetrag des Angebots in EUR.',
                ],
                'contact_name' => [
                    'type' => 'string',
                    'description' => 'Optional: Kundenname aus dem Angebot.',
                ],
            ],
            'required' => ['deal_id', 'quotation_external_id'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $found = $this->validateAndFindModel($arguments, $context, 'deal_id', SalesDeal::class, 'NOT_FOUND', 'Deal nicht gefunden.');
            if ($found['error']) {
                return $found['error'];
            }
            /** @var SalesDeal $deal */
            $deal = $found['model'];

            $resolved = $this->resolveTeam(['team_id' => $deal->team_id], $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }

            $quotationExternalId = trim($arguments['quotation_external_id'] ?? '');
            if ($quotationExternalId === '') {
                return ToolResult::error('VALIDATION_ERROR', 'quotation_external_id ist erforderlich.');
            }

            // Voucher-Status validieren
            if (isset($arguments['voucher_status'])) {
                $allowed = ['draft', 'open', 'accepted', 'rejected'];
                if (!in_array($arguments['voucher_status'], $allowed)) {
                    return ToolResult::error('VALIDATION_ERROR', 'voucher_status muss einer der folgenden Werte sein: ' . implode(', ', $allowed));
                }
            }

            $link = $deal->attachLexwareQuotation($quotationExternalId, [
                'quotation_number' => $arguments['quotation_number'] ?? null,
                'voucher_status' => $arguments['voucher_status'] ?? null,
                'voucher_date' => $arguments['voucher_date'] ?? null,
                'expiration_date' => $arguments['expiration_date'] ?? null,
                'total_amount' => $arguments['total_amount'] ?? null,
                'contact_name' => $arguments['contact_name'] ?? null,
            ]);

            return ToolResult::success([
                'id' => $link->id,
                'deal_id' => $deal->id,
                'deal_title' => $deal->title,
                'quotation_external_id' => $link->quotation_external_id,
                'quotation_number' => $link->quotation_number,
                'voucher_status' => $link->voucher_status,
                'total_amount' => (float) ($link->total_amount ?? 0),
                'message' => "Angebot '{$link->quotation_number}' mit Deal '{$deal->title}' verknüpft.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['sales', 'deals', 'quotation', 'lexoffice', 'lexware', 'angebot', 'link'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'write',
            'idempotent' => true,
            'confirmation_required' => false,
            'related_tools' => [
                'integrations.lexware.quotations.POST',
                'integrations.lexware.quotations.GET',
                'integrations.lexware.quotation.GET',
                'sales.deals.GET',
            ],
        ];
    }
}
