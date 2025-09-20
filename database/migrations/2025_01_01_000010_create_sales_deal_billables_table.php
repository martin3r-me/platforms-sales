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
        Schema::create('sales_deal_billables', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('sales_deal_id')->constrained('sales_deals')->cascadeOnDelete();
            
            // Billable Details
            $table->string('name'); // z.B. "Setup-Gebühr", "Monatliche Lizenz"
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2); // Betrag
            $table->string('billing_type')->default('one_time'); // 'one_time', 'recurring'
            $table->string('billing_interval')->nullable(); // 'monthly', 'quarterly', 'yearly' (nur bei recurring)
            $table->integer('duration_months')->nullable(); // Laufzeit in Monaten (nur bei recurring)
            $table->date('start_date')->nullable(); // Startdatum für wiederkehrende Zahlungen
            $table->date('end_date')->nullable(); // Enddatum für wiederkehrende Zahlungen
            
            // Berechneter Gesamtwert
            $table->decimal('total_value', 15, 2); // amount * duration_months (bei recurring)
            
            // Reihenfolge
            $table->integer('order')->default(0);
            
            // Status
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_deal_billables');
    }
};
