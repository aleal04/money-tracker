<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;
use App\Constant\LineSourceConstant;
use Illuminate\Support\Facades\Schema;
use App\Constant\SpaceFrequencyConstant;
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
     * Create the tables, with foreign keys ON, and seed the primary space (as the inserts script does) before each test.
     */
    protected function setUpCreatesSchemaTrait(): void
    {
        Schema::enableForeignKeyConstraints();
        $this->createSchema();
        DB::table('Space')->insert(['Name' => 'Salario', 'Frequency' => SpaceFrequencyConstant::BIWEEKLY, 'InitialBalance' => '0.00', 'IsPrimary' => true, 'Sort' => 0]);
    }

    /**
     * Create the tables in foreign key order.
     */
    protected function createSchema(): void
    {
        Schema::create('Space', function (Blueprint $table) {
            $table->id('IdSpace');
            $table->string('Name');
            $table->string('Frequency');
            $table->unsignedSmallInteger('CustomDays')->nullable();
            $table->decimal('InitialBalance', 14, 2)->default(0);
            $table->boolean('IsPrimary')->default(false);
            $table->integer('Sort')->default(0);
            $this->auditColumns($table);
            $table->string('ActiveName')->nullable()->virtualAs('CASE WHEN IsDeleted = 0 THEN Name ELSE NULL END');
            $table->boolean('ActivePrimary')->nullable()->virtualAs('CASE WHEN IsDeleted = 0 AND IsPrimary = 1 THEN 1 ELSE NULL END');
            $table->unique('ActiveName', 'UQ_Space_ActiveName');
            $table->unique('ActivePrimary', 'UQ_Space_ActivePrimary');
        });

        Schema::create('Card', function (Blueprint $table) {
            $table->id('IdCard');
            $table->foreignId('FkSpace')->constrained('Space', 'IdSpace', 'FK_Card_Space');
            $table->string('Name');
            $this->auditColumns($table);
            $table->string('ActiveName')->nullable()->virtualAs('CASE WHEN IsDeleted = 0 THEN Name ELSE NULL END');
            $table->unique('ActiveName', 'UQ_Card_ActiveName');
        });

        Schema::create('Template', function (Blueprint $table) {
            $table->id('IdTemplate');
            $table->foreignId('FkSpace')->constrained('Space', 'IdSpace', 'FK_Template_Space');
            $table->foreignId('FkTargetSpace')->nullable()->constrained('Space', 'IdSpace', 'FK_Template_TargetSpace');
            $table->string('Name');
            $table->string('Category');
            $table->decimal('Estimated', 14, 2);
            $table->string('Frequency');
            $table->unsignedTinyInteger('DayOfMonth')->nullable();
            $table->boolean('Active')->default(true);
            $this->auditColumns($table);
        });

        Schema::create('Period', function (Blueprint $table) {
            $table->id('IdPeriod');
            $table->foreignId('FkSpace')->constrained('Space', 'IdSpace', 'FK_Period_Space');
            $table->date('StartDate');
            $table->date('EndDate');
            $table->boolean('Closed')->default(false);
            $this->auditColumns($table);
            $table->date('ActiveStart')->nullable()->virtualAs('CASE WHEN IsDeleted = 0 THEN StartDate ELSE NULL END');
            $table->unique(['FkSpace', 'ActiveStart'], 'UQ_Period_FkSpace_ActiveStart');
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
            $table->foreignId('FkTargetSpace')->nullable()->constrained('Space', 'IdSpace', 'FK_Line_TargetSpace');
            $table->foreignId('FkTransferFromLine')->nullable()->constrained('Line', 'IdLine', 'FK_Line_TransferFromLine')->nullOnDelete();
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
