<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Sales\Models\SalesRevenueCategory;
use Illuminate\Support\Str;

/**
 * Tool zum Anlegen einer Umsatzkategorie (Lookup "revenue_categories").
 *
 * Datengetrieben (kein Hardcode-Enum): fehlende Stufen werden hierüber als Daten
 * angelegt.
 */
class CreateRevenueCategoryTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'sales.revenue_categories.POST';
    }

    public function getDescription(): string
    {
        return 'POST /sales/revenue-categories - Legt eine neue Umsatzkategorie (Lookup) an. Parameter: name (required, z.B. "A: > 100K"). code (optional, sonst aus name abgeleitet, muss eindeutig sein). order (optional). is_active (optional, Standard true). Prüfe vorher mit "sales.lookups.GET" (type=revenue_categories).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Required: Anzeigename der Umsatzkategorie (z.B. "A: > 100K").',
                ],
                'code' => [
                    'type' => 'string',
                    'description' => 'Optional: eindeutiger technischer Code (z.B. "a"). Wird ohne Angabe aus dem Namen abgeleitet.',
                ],
                'order' => [
                    'type' => 'integer',
                    'description' => 'Optional: Sortierreihenfolge. Standard: 0.',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Ob die Kategorie aktiv/auswählbar ist. Standard: true.',
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

            $code = trim((string) ($arguments['code'] ?? ''));
            if ($code === '') {
                $code = Str::slug($name, '_');
            }
            if ($code === '') {
                return ToolResult::error('VALIDATION_ERROR', 'code konnte nicht aus dem Namen abgeleitet werden – bitte code explizit angeben.');
            }

            if (SalesRevenueCategory::where('code', $code)->exists()) {
                return ToolResult::error('VALIDATION_ERROR', "Eine Umsatzkategorie mit code '{$code}' existiert bereits. Nutze sales.revenue_categories.PUT zum Ändern.");
            }

            $category = SalesRevenueCategory::create([
                'name' => $name,
                'code' => $code,
                'order' => (int) ($arguments['order'] ?? 0),
                'is_active' => $arguments['is_active'] ?? true,
            ]);

            return ToolResult::success([
                'id' => $category->id,
                'uuid' => $category->uuid,
                'name' => $category->name,
                'code' => $category->code,
                'order' => $category->order,
                'is_active' => $category->is_active,
                'message' => "Umsatzkategorie '{$category->name}' (code: {$category->code}) erstellt.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der Umsatzkategorie: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['sales', 'lookup', 'revenue_category', 'create'],
            'risk_level' => 'write',
        ];
    }
}
