<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Platform\Sales\Models\SalesLostReason;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Verlustgründe als datengetriebener Lookup (statt Hardcode-Enum im Code).
     * Jede Instanz pflegt ihre eigenen Gründe über MCP/Seeder. Der gespeicherte
     * Code landet weiterhin in sales_deals.lost_reason_code.
     */
    public function up(): void
    {
        Schema::create('sales_lost_reasons', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Generisches Basis-Set, damit bestehende lost_reason_code-Werte sofort
        // gültig bleiben. Idempotent – jede Instanz kann es danach frei anpassen.
        foreach (SalesLostReason::getDefaultLostReasons() as $reason) {
            SalesLostReason::firstOrCreate(
                ['code' => $reason['code']],
                $reason + ['is_active' => true]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_lost_reasons');
    }
};
