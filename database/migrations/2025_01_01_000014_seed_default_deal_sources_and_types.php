<?php

use Illuminate\Database\Migrations\Migration;
use Platform\Sales\Models\SalesDealSource;
use Platform\Sales\Models\SalesDealType;

return new class extends Migration
{
    /**
     * Befüllt die Lookup-Tabellen mit dem generischen Basis-Set.
     *
     * Die Tabellen wurden in 000006/000007 angelegt, aber nie befüllt (Seeder
     * laufen beim Deploy nicht automatisch). Dadurch blieben die Dropdowns
     * "Deal Quelle" und "Deal Typ" leer. Hier idempotent nachgezogen, damit
     * bestehende Instanzen beim nächsten Deploy – und jede neue Instanz – ein
     * sinnvolles Basis-Set haben. Jede Instanz kann es danach frei anpassen.
     */
    public function up(): void
    {
        foreach (SalesDealSource::getDefaults() as $source) {
            SalesDealSource::firstOrCreate(['name' => $source['name']], $source + ['is_active' => true]);
        }

        foreach (SalesDealType::getDefaults() as $type) {
            SalesDealType::firstOrCreate(['name' => $type['name']], $type + ['is_active' => true]);
        }
    }

    /**
     * Bewusst kein Entfernen: Die Einträge könnten inzwischen in Deals
     * referenziert oder angepasst worden sein.
     */
    public function down(): void
    {
        // no-op
    }
};
