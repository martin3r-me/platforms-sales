<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Verknüpft einen Deal optional mit einer Umsatzkategorie (Lookup).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_deals', function (Blueprint $table) {
            $table->foreignId('sales_revenue_category_id')
                ->nullable()
                ->after('sales_deal_type_id')
                ->constrained('sales_revenue_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_deals', function (Blueprint $table) {
            $table->dropForeign(['sales_revenue_category_id']);
            $table->dropColumn('sales_revenue_category_id');
        });
    }
};
