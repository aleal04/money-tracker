<?php

namespace Tests\Feature\Database;

use Tests\TestCase;
use App\Models\Period\LineModel;
use App\Models\Space\SpaceModel;
use App\Models\Period\PeriodModel;
use App\Models\Installment\CardModel;
use App\Models\Template\TemplateModel;
use App\Models\Installment\InstallmentModel;

class MysqlScriptTest extends TestCase
{
    /**
     * Every table, primary key, fillable column and audit column used by the models exists in the MySQL tables script.
     */
    public function test_models_match_mysql_tables_script(): void
    {
        $sql = file_get_contents(base_path('database/scripts/tables/1. Money Tracker - Tables.sql'));

        foreach ([SpaceModel::class, CardModel::class, TemplateModel::class, PeriodModel::class, LineModel::class, InstallmentModel::class] as $class) {
            $model = new $class;
            $this->assertSame(1, preg_match('/CREATE TABLE IF NOT EXISTS `?'.$model->getTable().'`? \((.*?)\n\) ENGINE/s', $sql, $match), $model->getTable().' is missing in the script');

            foreach ([...$model->getFillable(), $model->getKeyName(), 'IsDeleted', 'CreatedBy', 'UpdatedBy', 'DeletedBy', 'CreatedAt', 'UpdatedAt', 'DeletedAt'] as $column) {
                $this->assertMatchesRegularExpression('/^\s+'.$column.'\s/m', $match[1], $model->getTable().'.'.$column.' is missing in the script');
            }
        }
    }
}
