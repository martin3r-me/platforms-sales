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
        Schema::create('sales_deals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('sales_board_id')->nullable()->constrained('sales_boards')->nullOnDelete();
            $table->foreignId('sales_board_slot_id')->nullable()->constrained('sales_board_slots')->nullOnDelete();
            $table->integer('order')->default(0);
            $table->integer('slot_order')->default(0);
            
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('user_in_charge_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('deal_value', 15, 2)->nullable();
            $table->integer('probability_percent')->nullable();
            $table->string('deal_source')->nullable();
            $table->string('deal_type')->nullable();

            $table->boolean('is_done')->default(false);
            $table->timestamp('done_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_deals');
    }
};
