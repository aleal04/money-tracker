<?php

namespace Tests\Concerns;

use App\Constant\LineSourceConstant;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * Builds the app schema on sqlite :memory: with the Schema builder.
 *
 * The source of truth is database/scripts/tables (MySQL); this mirrors it for the tests.
 */
trait CreatesSchemaTrait
{
    /**
     * Create the five tables, with foreign keys ON, before each test.
     */
    protected function setUpCreatesSchemaTrait(): void
    {
        Schema::enableForeignKeyConstraints();
        $this->createSchema();
    }

    /**
     * Create the tables in foreign key order.
     */
    protected function createSchema(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->decimal('estimated', 14, 2);
            $table->string('frequency');
            $table->unsignedTinyInteger('half')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('half');
            $table->boolean('closed')->default(false);
            $table->timestamps();
            $table->unique(['year', 'month', 'half']);
        });

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
            $table->unsignedBigInteger('cuotas_card_id')
                ->nullable()
                ->virtualAs("CASE WHEN source = '".LineSourceConstant::INSTALLMENTS."' THEN card_id ELSE NULL END");
            $table->timestamps();
            $table->unique(['period_id', 'cuotas_card_id']);
        });

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
}
