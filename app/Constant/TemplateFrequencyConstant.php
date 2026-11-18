<?php

namespace App\Constant;

final class TemplateFrequencyConstant
{
    public const QUINCENAL = 'quincenal';

    public const MENSUAL_DIVIDIDO = 'mensual_dividido';

    public const MENSUAL_EN_QUINCENA = 'mensual_en_quincena';

    public const ALL = [self::QUINCENAL, self::MENSUAL_DIVIDIDO, self::MENSUAL_EN_QUINCENA];
}
