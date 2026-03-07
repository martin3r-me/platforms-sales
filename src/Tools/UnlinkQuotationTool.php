<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Sales\Tools\Concerns\ResolvesSalesTeam;
use Platform\Sales\Models\SalesDeal;

class UnlinkQuotationTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations, ResolvesSalesTeam;

    public function getName(): string
    {
        return 'sales.deal_quotations.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /sales/deal_quotations - Entfernt die Verknüpfung zwischen einem Deal und einem Lexoffice-Angebot. Das Angebot in Lexoffice bleibt bestehen, nur die Verknüpfung wird gelöscht.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'deal_id' => [
                    'type' => 'integer',
                    'description' => 'Required: ID des Deals.',
                ],
                'quotation_external_id' => [
                    'type' => 'string',
                    'description' => 'Required: UUID des Lexoffice-Angebots.',
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

            $removed = $deal->detachLexwareQuotation($quotationExternalId);

            if (!$removed) {
                return ToolResult::error('NOT_FOUND', 'Verknüpfung nicht gefunden. Nutze "sales.deal_quotations.GET" um verknüpfte Angebote zu sehen.');
            }

            return ToolResult::success([
                'deal_id' => $deal->id,
                'deal_title' => $deal->title,
                'quotation_external_id' => $quotationExternalId,
                'message' => "Verknüpfung zum Angebot '{$quotationExternalId}' vom Deal '{$deal->title}' entfernt.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['sales', 'deals', 'quotation', 'lexoffice', 'unlink'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'write',
            'idempotent' => true,
            'confirmation_required' => false,
        ];
    }
}
