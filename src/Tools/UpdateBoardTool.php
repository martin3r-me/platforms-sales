<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Sales\Tools\Concerns\ResolvesSalesTeam;
use Platform\Sales\Models\SalesBoard;
use Platform\Crm\Models\CrmCompany;
use Platform\Crm\Models\CrmCompanyLink;

class UpdateBoardTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations, ResolvesSalesTeam;

    public function getName(): string
    {
        return 'sales.boards.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /sales/boards/{id} - Aktualisiert ein bestehendes Vertriebsboard (Name, Beschreibung). Nutze "sales.boards.GET" um die Board-ID zu finden.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'board_id' => [
                    'type' => 'integer',
                    'description' => 'Required: ID des Boards.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Name des Boards.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue Beschreibung.',
                ],
                'partner_company_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: CRM-Company als Partner dieses Boards setzen. 0 oder null entfernt den Partner. Nutze "crm.companies.GET".',
                ],
            ],
            'required' => ['board_id'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $found = $this->validateAndFindModel($arguments, $context, 'board_id', SalesBoard::class, 'NOT_FOUND', 'Board nicht gefunden.');
            if ($found['error']) {
                return $found['error'];
            }
            /** @var SalesBoard $board */
            $board = $found['model'];

            // Team-Zugriff prüfen
            $resolved = $this->resolveTeam(['team_id' => $board->team_id], $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }

            $updated = [];

            if (isset($arguments['name'])) {
                $name = trim($arguments['name']);
                if ($name === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'Name darf nicht leer sein.');
                }
                $board->name = $name;
                $updated[] = 'name';
            }

            if (array_key_exists('description', $arguments)) {
                $board->description = $arguments['description'];
                $updated[] = 'description';
            }

            // Partner-Company setzen/entfernen
            $partnerName = null;
            if (array_key_exists('partner_company_id', $arguments)) {
                $partnerId = $arguments['partner_company_id'];
                if ($partnerId === 0 || $partnerId === '0') { $partnerId = null; }

                // Bestehende Partner-Verknüpfung(en) dieses Boards entfernen
                CrmCompanyLink::where('linkable_type', $board->getMorphClass())
                    ->where('linkable_id', $board->id)
                    ->where('team_id', $board->team_id)
                    ->delete();

                if ($partnerId) {
                    $company = CrmCompany::where('id', (int) $partnerId)
                        ->where('team_id', $board->team_id)
                        ->first();
                    if (!$company) {
                        return ToolResult::error('NOT_FOUND', 'Partner-Company nicht gefunden oder gehört nicht zum Team. Nutze "crm.companies.GET".');
                    }
                    CrmCompanyLink::create([
                        'company_id' => $company->id,
                        'linkable_id' => $board->id,
                        'linkable_type' => $board->getMorphClass(),
                        'team_id' => $board->team_id,
                        'created_by_user_id' => $context->user->id,
                    ]);
                    $partnerName = $company->name;
                }
                $updated[] = 'partner';
            }

            if (empty($updated)) {
                return ToolResult::error('VALIDATION_ERROR', 'Keine Änderungen angegeben. Verfügbare Felder: name, description, partner_company_id.');
            }

            $board->save();

            return ToolResult::success([
                'id' => $board->id,
                'name' => $board->name,
                'description' => $board->description,
                'partner' => $partnerName,
                'updated_fields' => $updated,
                'message' => "Board '{$board->name}' aktualisiert.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['sales', 'boards', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'write',
            'idempotent' => true,
            'confirmation_required' => false,
        ];
    }
}
