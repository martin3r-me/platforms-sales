<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_deals', function (Blueprint $table) {
            // On-Hold: Deal ist pausiert (bleibt offen, is_done=false, lost_at=null),
            // wird aber aus der aktiven Tageslinse ausgeblendet.
            $table->timestamp('on_hold_at')->nullable()->after('lost_reason');
            // Strukturierter Verlustgrund (N1/N2/N3). lost_reason bleibt Freitext-Detail.
            $table->string('lost_reason_code')->nullable()->after('on_hold_at');
        });
    }

    public function down(): void
    {
        Schema::table('sales_deals', function (Blueprint $table) {
            $table->dropColumn(['on_hold_at', 'lost_reason_code']);
        });
    }
};
