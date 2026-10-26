<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Sales\Models\SalesLostReason;
use Illuminate\Support\Str;

/**
 * Tool zum Anlegen eines Verlustgrunds (Lookup "lost_reasons").
 *
 * Verlustgründe sind bewusst datengetrieben (kein Hardcode-Enum): fehlende
 * Gründe werden hierüber als Daten angelegt. Der Code (lowercase snake) wird in
 * sales_deals.lost_reason_code gespeichert.
 */
class CreateLostReasonTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'sales.lost_reasons.POST';
    }

    public function getDescription(): string
    {
        return 'POST /sales/lost-reasons - Legt einen neuen Verlustgrund (Lookup) an. Parameter: name (required). code (optional, sonst aus name als lowercase-snake abgeleitet, muss eindeutig sein). order (optional). is_active (optional, Standard true). Prüfe vorher mit "sales.lookups.GET" (type=lost_reasons), ob der Grund schon existiert.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Required: Anzeigename des Verlustgrunds (z.B. "Budget gestrichen").',
                ],
                'code' => [
                    'type' => 'string',
                    'description' => 'Optional: eindeutiger technischer Code (lowercase snake). Wird ohne Angabe aus dem Namen abgeleitet (z.B. "budget_gestrichen").',
                ],
                'order' => [
                    'type' => 'integer',
                    'description' => 'Optional: Sortierreihenfolge. Standard: 0.',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Ob der Grund aktiv/auswählbar ist. Standard: true.',
                ],
            ],
            'required' => ['name'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $name = trim((string) ($arguments['name'] ?? ''));
            if ($name === '') {
                return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');
            }

            // Code ableiten, falls nicht angegeben (lowercase snake)
            $code = trim((string) ($arguments['code'] ?? ''));
            if ($code === '') {
                $code = Str::slug($name, '_');
            }
            if ($code === '') {
                return ToolResult::error('VALIDATION_ERROR', 'code konnte nicht aus dem Namen abgeleitet werden – bitte code explizit angeben.');
            }

            if (SalesLostReason::where('code', $code)->exists()) {
                return ToolResult::error('VALIDATION_ERROR', "Ein Verlustgrund mit code '{$code}' existiert bereits. Nutze sales.lost_reasons.PUT zum Ändern.");
            }

            $reason = SalesLostReason::create([
                'name' => $name,
                'code' => $code,
                'order' => (int) ($arguments['order'] ?? 0),
                'is_active' => $arguments['is_active'] ?? true,
            ]);

            return ToolResult::success([
                'id' => $reason->id,
                'uuid' => $reason->uuid,
                'name' => $reason->name,
                'code' => $reason->code,
                'order' => $reason->order,
                'is_active' => $reason->is_active,
                'message' => "Verlustgrund '{$reason->name}' (code: {$reason->code}) erstellt.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Verlustgrunds: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['sales', 'lookup', 'lost_reason', 'create'],
            'risk_level' => 'write',
        ];
    }
}
