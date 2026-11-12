<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Generischer Sales-Lookup "revenue_categories" (Umsatzkategorie, z. B. A/B/C).
 *
 * Bewusst OHNE Seed ausgeliefert: die konkreten Stufen/Schwellen sind Mandanten-
 * Fachdaten und werden im UI bzw. per MCP als Daten gepflegt – kein Hardcode.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_revenue_categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_revenue_categories');
    }
};
