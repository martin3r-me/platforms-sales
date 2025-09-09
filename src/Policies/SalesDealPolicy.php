<?php

namespace Platform\Sales\Policies;

use Platform\Core\Models\User;
use Platform\Sales\Models\SalesDeal;

class SalesDealPolicy
{
    /**
     * Darf der User diesen Deal sehen?
     */
    public function view(User $user, SalesDeal $deal): bool
    {
        // Persönlicher Deal (Owner)
        if ($deal->user_id === $user->id) {
            return true;
        }

        // Team-Deal: User ist im aktuellen Team
        if (
            $deal->team_id &&
            $user->currentTeam &&
            $deal->team_id === $user->currentTeam->id
        ) {
            return true;
        }

        // User ist verantwortlich für den Deal
        if ($deal->user_in_charge_id === $user->id) {
            return true;
        }

        // Kein Zugriff
        return false;
    }

    /**
     * Darf der User diesen Deal bearbeiten?
     */
    public function update(User $user, SalesDeal $deal): bool
    {
        // Persönlicher Deal (Owner)
        if ($deal->user_id === $user->id) {
            return true;
        }

        // Team-Deal: User ist im aktuellen Team
        if (
            $deal->team_id &&
            $user->currentTeam &&
            $deal->team_id === $user->currentTeam->id
        ) {
            return true;
        }

        // User ist verantwortlich für den Deal
        if ($deal->user_in_charge_id === $user->id) {
            return true;
        }

        // Kein Zugriff
        return false;
    }

    /**
     * Darf der User diesen Deal löschen?
     */
    public function delete(User $user, SalesDeal $deal): bool
    {
        // Nur der Ersteller darf löschen!
        return $deal->user_id === $user->id;
    }

    /**
     * Darf der User diesen Deal als gewonnen markieren?
     */
    public function complete(User $user, SalesDeal $deal): bool
    {
        // Persönlicher Deal (Owner)
        if ($deal->user_id === $user->id) {
            return true;
        }

        // User ist verantwortlich für den Deal
        if ($deal->user_in_charge_id === $user->id) {
            return true;
        }

        // Team-Deal: User ist im aktuellen Team
        if (
            $deal->team_id &&
            $user->currentTeam &&
            $deal->team_id === $user->currentTeam->id
        ) {
            return true;
        }

        // Kein Zugriff
        return false;
    }

    // Weitere Methoden nach Bedarf (create, assign, ...)
}