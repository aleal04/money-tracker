<?php

namespace Tests\Concerns;

use App\Constant\LineSourceConstant;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * Builds the app schema on sqlite :memory: with the Schema builder.
 *
 * The source of truth is database/scripts/tables (MySQL); this mirrors it for the tests,
 * with the same table, column and constraint names.
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
        Schema::create('Card', function (Blueprint $table) {
            $table->id('IdCard');
            $table->string('Name');
            $this->auditColumns($table);
            $table->string('ActiveName')->nullable()->virtualAs('CASE WHEN IsDeleted = 0 THEN Name ELSE NULL END');
            $table->unique('ActiveName', 'UQ_Card_ActiveName');
        });

        Schema::create('Template', function (Blueprint $table) {
            $table->id('IdTemplate');
            $table->string('Name');
            $table->string('Category');
            $table->decimal('Estimated', 14, 2);
            $table->string('Frequency');
            $table->unsignedTinyInteger('Half')->nullable();
            $table->boolean('Active')->default(true);
            $this->auditColumns($table);
        });

        Schema::create('Period', function (Blueprint $table) {
            $table->id('IdPeriod');
            $table->unsignedSmallInteger('Year');
            $table->unsignedTinyInteger('Month');
            $table->unsignedTinyInteger('Half');
            $table->boolean('Closed')->default(false);
            $this->auditColumns($table);
            $table->unique(['Year', 'Month', 'Half'], 'UQ_Period_Year_Month_Half');
        });

        Schema::create('Line', function (Blueprint $table) {
            $table->id('IdLine');
            $table->foreignId('FkPeriod')->constrained('Period', 'IdPeriod', 'FK_Line_Period')->cascadeOnDelete();
            $table->string('Category');
            $table->string('Name');
            $table->decimal('Estimated', 14, 2)->default(0);
            $table->decimal('Actual', 14, 2)->nullable();
            $table->boolean('Paid')->default(false);
            $table->dateTime('PaidAt')->nullable();
            $table->string('Source')->default(LineSourceConstant::MANUAL);
            $table->foreignId('FkTemplate')->nullable()->constrained('Template', 'IdTemplate', 'FK_Line_Template')->nullOnDelete();
            $table->foreignId('FkCard')->nullable()->constrained('Card', 'IdCard', 'FK_Line_Card')->nullOnDelete();
            $table->foreignId('FkCarriedToLine')->nullable()->constrained('Line', 'IdLine', 'FK_Line_CarriedToLine')->nullOnDelete();
            $table->integer('Sort')->default(0);
            $this->auditColumns($table);
            $table->unsignedBigInteger('CuotasCardKey')
                ->nullable()
                ->virtualAs("CASE WHEN Source = '".LineSourceConstant::INSTALLMENTS."' AND IsDeleted = 0 THEN FkCard ELSE NULL END");
            $table->unique(['FkPeriod', 'CuotasCardKey'], 'UQ_Line_FkPeriod_CuotasCardKey');
        });

        Schema::create('Installment', function (Blueprint $table) {
            $table->id('IdInstallment');
            $table->foreignId('FkCard')->constrained('Card', 'IdCard', 'FK_Installment_Card')->restrictOnDelete();
            $table->string('Merchant');
            $table->string('Item');
            $table->unsignedSmallInteger('TotalInstallments');
            $table->unsignedSmallInteger('PaidInstallments')->default(0);
            $table->string('Type');
            $table->decimal('InitialBalance', 14, 2);
            $table->decimal('InstallmentAmount', 14, 2)->nullable();
            $table->decimal('BalanceOverride', 14, 2)->nullable();
            $table->boolean('CancelSoon')->default(false);
            $table->string('LastAdvancedMonth', 7);
            $this->auditColumns($table);
        });
    }

    /**
     * Add the ssi audit and logical delete columns to a table.
     *
     * @param  Blueprint  $table  The table being created.
     */
    private function auditColumns(Blueprint $table): void
    {
        $table->boolean('IsDeleted')->default(false);
        $table->string('CreatedBy', 50)->nullable();
        $table->string('UpdatedBy', 50)->nullable();
        $table->string('DeletedBy', 50)->nullable();
        $table->dateTime('CreatedAt')->nullable()->useCurrent();
        $table->dateTime('UpdatedAt')->nullable();
        $table->dateTime('DeletedAt')->nullable();
    }
}
