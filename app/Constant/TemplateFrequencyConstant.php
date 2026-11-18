<?php

namespace App\Constant;

final class TemplateFrequencyConstant
{
    public const CADA_PERIODO = 'cada_periodo';

    public const MENSUAL_REPARTIDO = 'mensual_repartido';

    public const MENSUAL_DIA = 'mensual_dia';

    public const ALL = [self::CADA_PERIODO, self::MENSUAL_REPARTIDO, self::MENSUAL_DIA];
}
