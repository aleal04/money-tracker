<?php

use App\Constant\LineSourceConstant;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Create the lines table, including the generated column that makes the installments line unique per (period, card).
     */
    public function up(): void
    {
        Schema::create('lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('periods')->cascadeOnDelete();
            $table->string('category');
            $table->string('name');
            $table->decimal('estimated', 14, 2)->default(0);
            $table->decimal('actual', 14, 2)->nullable();
            $table->boolean('paid')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->string('source')->default(LineSourceConstant::MANUAL);
            $table->foreignId('template_id')->nullable()->constrained('templates')->nullOnDelete();
            $table->foreignId('card_id')->nullable()->constrained('cards')->nullOnDelete();
            $table->foreignId('carried_to_line_id')->nullable()->constrained('lines')->nullOnDelete();
            $table->integer('sort')->default(0);
            // MySQL has no partial indexes: NULL for non-installments lines is ignored by UNIQUE.
            $table->unsignedBigInteger('cuotas_card_id')
                ->nullable()
                ->virtualAs("CASE WHEN source = '".LineSourceConstant::INSTALLMENTS."' THEN card_id ELSE NULL END");
            $table->timestamps();

            $table->unique(['period_id', 'cuotas_card_id']);
        });
    }

    /**
     * Drop the lines table.
     */
    public function down(): void
    {
        Schema::dropIfExists('lines');
    }
};
