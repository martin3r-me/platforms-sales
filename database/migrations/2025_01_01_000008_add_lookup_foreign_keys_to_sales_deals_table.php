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
            // Foreign Keys zu Lookup-Tabellen hinzufügen
            $table->foreignId('sales_priority_id')->nullable()->constrained('sales_priorities')->nullOnDelete();
            $table->foreignId('sales_deal_source_id')->nullable()->constrained('sales_deal_sources')->nullOnDelete();
            $table->foreignId('sales_deal_type_id')->nullable()->constrained('sales_deal_types')->nullOnDelete();
            
            // Weitere komplexe Felder
            $table->decimal('expected_value', 15, 2)->nullable()->after('deal_value');
            $table->decimal('minimum_value', 15, 2)->nullable()->after('expected_value');
            $table->decimal('maximum_value', 15, 2)->nullable()->after('minimum_value');
            $table->date('close_date')->nullable()->after('due_date');
            $table->text('notes')->nullable()->after('description');
            $table->string('competitor')->nullable()->after('deal_type');
            $table->string('next_step')->nullable()->after('competitor');
            $table->date('next_step_date')->nullable()->after('next_step');
            $table->boolean('is_hot')->default(false)->after('is_done');
            $table->boolean('is_starred')->default(false)->after('is_hot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_deals', function (Blueprint $table) {
            $table->dropForeign(['sales_priority_id']);
            $table->dropForeign(['sales_deal_source_id']);
            $table->dropForeign(['sales_deal_type_id']);
            
            $table->dropColumn([
                'sales_priority_id',
                'sales_deal_source_id', 
                'sales_deal_type_id',
                'expected_value',
                'minimum_value',
                'maximum_value',
                'close_date',
                'notes',
                'competitor',
                'next_step',
                'next_step_date',
                'is_hot',
                'is_starred'
            ]);
        });
    }
};
