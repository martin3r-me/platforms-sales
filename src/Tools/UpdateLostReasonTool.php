<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Sales\Models\SalesLostReason;

/**
 * Tool zum Aktualisieren eines Verlustgrunds (Lookup "lost_reasons").
 */
class UpdateLostReasonTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'sales.lost_reasons.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /sales/lost-reasons/{id} - Aktualisiert einen Verlustgrund (Lookup). Parameter: id (required). name, code, order, is_active (optional). Nutze "sales.lookups.GET" (type=lost_reasons) für die ID. is_active=false deaktiviert einen Grund, ohne ihn zu löschen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'Required: ID des Verlustgrunds.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: neuer Anzeigename.',
                ],
                'code' => [
                    'type' => 'string',
                    'description' => 'Optional: neuer eindeutiger Code (lowercase snake).',
                ],
                'order' => [
                    'type' => 'integer',
                    'description' => 'Optional: neue Sortierreihenfolge.',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: aktiv/inaktiv setzen.',
                ],
            ],
            'required' => ['id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $id = $arguments['id'] ?? null;
            if (!is_numeric($id)) {
                return ToolResult::error('VALIDATION_ERROR', 'id ist erforderlich.');
            }

            $reason = SalesLostReason::find((int) $id);
            if (!$reason) {
                return ToolResult::error('NOT_FOUND', 'Verlustgrund nicht gefunden. Nutze "sales.lookups.GET" (type=lost_reasons).');
            }

            $updated = [];

            if (array_key_exists('name', $arguments)) {
                $name = trim((string) $arguments['name']);
                if ($name === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'name darf nicht leer sein.');
                }
                $reason->name = $name;
                $updated[] = 'name';
            }

            if (array_key_exists('code', $arguments)) {
                $code = trim((string) $arguments['code']);
                if ($code === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'code darf nicht leer sein.');
                }
                if (SalesLostReason::where('code', $code)->where('id', '!=', $reason->id)->exists()) {
                    return ToolResult::error('VALIDATION_ERROR', "Ein anderer Verlustgrund mit code '{$code}' existiert bereits.");
                }
                $reason->code = $code;
                $updated[] = 'code';
            }

            if (array_key_exists('order', $arguments)) {
                $reason->order = (int) $arguments['order'];
                $updated[] = 'order';
            }

            if (array_key_exists('is_active', $arguments)) {
                $reason->is_active = (bool) $arguments['is_active'];
                $updated[] = 'is_active';
            }

            if (empty($updated)) {
                return ToolResult::error('VALIDATION_ERROR', 'Keine Änderungen angegeben.');
            }

            $reason->save();

            return ToolResult::success([
                'id' => $reason->id,
                'uuid' => $reason->uuid,
                'name' => $reason->name,
                'code' => $reason->code,
                'order' => $reason->order,
                'is_active' => $reason->is_active,
                'updated_fields' => $updated,
                'message' => "Verlustgrund '{$reason->name}' aktualisiert.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Verlustgrunds: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['sales', 'lookup', 'lost_reason', 'update'],
            'risk_level' => 'write',
        ];
    }
}
