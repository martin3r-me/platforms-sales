<?php

namespace Platform\Sales\Policies;

use Platform\Core\Models\User;
use Platform\Sales\Models\SalesBoardTemplate;

class SalesBoardTemplatePolicy
{
    /**
     * Darf der User dieses Template sehen?
     */
    public function view(User $user, SalesBoardTemplate $template): bool
    {
        // System-Templates sind für alle sichtbar
        if ($template->is_system) {
            return true;
        }

        // Persönliches Template (Owner)
        if ($template->user_id === $user->id) {
            return true;
        }

        // Team-Template: User ist im aktuellen Team
        if (
            $template->team_id &&
            $user->currentTeam &&
            $template->team_id === $user->currentTeam->id
        ) {
            return true;
        }

        // Kein Zugriff
        return false;
    }

    /**
     * Darf der User dieses Template bearbeiten?
     */
    public function update(User $user, SalesBoardTemplate $template): bool
    {
        // System-Templates können nicht bearbeitet werden
        if ($template->is_system) {
            return false;
        }

        // Persönliches Template (Owner)
        if ($template->user_id === $user->id) {
            return true;
        }

        // Team-Template: User ist im aktuellen Team
        if (
            $template->team_id &&
            $user->currentTeam &&
            $template->team_id === $user->currentTeam->id
        ) {
            return true;
        }

        // Kein Zugriff
        return false;
    }

    /**
     * Darf der User dieses Template löschen?
     */
    public function delete(User $user, SalesBoardTemplate $template): bool
    {
        // System-Templates können nicht gelöscht werden
        if ($template->is_system) {
            return false;
        }

        // Nur der Ersteller darf löschen!
        return $template->user_id === $user->id;
    }

    /**
     * Darf der User Templates erstellen?
     */
    public function create(User $user): bool
    {
        // Alle authentifizierten User können Templates erstellen
        return true;
    }
}
