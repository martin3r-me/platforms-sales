<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales_deals', function (Blueprint $table) {
            // Billing-Intervalle hinzufügen
            $table->enum('billing_interval', ['one_time', 'monthly', 'quarterly', 'yearly'])->default('one_time')->after('deal_value');
            $table->integer('billing_duration_months')->nullable()->after('billing_interval'); // Für wiederkehrende Zahlungen
            $table->decimal('monthly_recurring_value', 15, 2)->nullable()->after('billing_duration_months'); // MRR für wiederkehrende Deals
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_deals', function (Blueprint $table) {
            $table->dropColumn([
                'billing_interval',
                'billing_duration_months',
                'monthly_recurring_value'
            ]);
        });
    }
};
