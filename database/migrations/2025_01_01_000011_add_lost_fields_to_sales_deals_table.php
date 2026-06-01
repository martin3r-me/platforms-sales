<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_deals', function (Blueprint $table) {
            $table->timestamp('lost_at')->nullable()->after('done_at');
            $table->string('lost_reason')->nullable()->after('lost_at');
        });
    }

    public function down(): void
    {
        Schema::table('sales_deals', function (Blueprint $table) {
            $table->dropColumn(['lost_at', 'lost_reason']);
        });
    }
};
