<?php

namespace Platform\Sales\Policies;

use Platform\Core\Models\User;
use Platform\Sales\Models\SalesBoard;

class SalesBoardPolicy
{
    /**
     * Darf der User dieses Sales Board sehen?
     */
    public function view(User $user, SalesBoard $board): bool
    {
        // Persönliches Board (Owner)
        if ($board->user_id === $user->id) {
            return true;
        }

        // Team-Board: User ist im aktuellen Team
        if (
            $board->team_id &&
            $user->currentTeam &&
            $board->team_id === $user->currentTeam->id
        ) {
            return true;
        }

        // Kein Zugriff
        return false;
    }

    /**
     * Darf der User dieses Sales Board bearbeiten?
     */
    public function update(User $user, SalesBoard $board): bool
    {
        // Persönliches Board (Owner)
        if ($board->user_id === $user->id) {
            return true;
        }

        // Team-Board: User ist im aktuellen Team
        if (
            $board->team_id &&
            $user->currentTeam &&
            $board->team_id === $user->currentTeam->id
        ) {
            return true;
        }

        // Kein Zugriff
        return false;
    }

    /**
     * Darf der User dieses Sales Board löschen?
     */
    public function delete(User $user, SalesBoard $board): bool
    {
        // Nur der Ersteller darf löschen!
        return $board->user_id === $user->id;
    }

    // Weitere Methoden nach Bedarf (create, assign, invite, ...)
}
