<?php

namespace App\Constant;

final class TemplateFrequencyConstant
{
    public const QUINCENAL = 'quincenal';

    public const MENSUAL_DIVIDIDO = 'mensual_dividido';

    public const MENSUAL_EN_QUINCENA = 'mensual_en_quincena';

    public const CADA_PERIODO = 'cada_periodo';

    public const MENSUAL_REPARTIDO = 'mensual_repartido';

    public const MENSUAL_DIA = 'mensual_dia';

    public const ALL = [self::QUINCENAL, self::MENSUAL_DIVIDIDO, self::MENSUAL_EN_QUINCENA];
}
