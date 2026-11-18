<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Create the installments table.
     */
    public function up(): void
    {
        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained('cards')->restrictOnDelete();
            $table->string('merchant');
            $table->string('item');
            $table->unsignedSmallInteger('total_installments');
            $table->unsignedSmallInteger('paid_installments')->default(0);
            $table->string('type');
            $table->decimal('initial_balance', 14, 2);
            $table->decimal('installment_amount', 14, 2)->nullable();
            $table->decimal('balance_override', 14, 2)->nullable();
            $table->boolean('cancel_soon')->default(false);
            $table->string('last_advanced_month', 7);
            $table->timestamps();
        });
    }

    /**
     * Drop the installments table.
     */
    public function down(): void
    {
        Schema::dropIfExists('installments');
    }
};
