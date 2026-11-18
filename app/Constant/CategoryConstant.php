<?php

namespace App\Constant;

final class CategoryConstant
{
    public const INGRESO = 'ingreso';

    public const SERVICIO = 'servicio';

    public const GASTO = 'gasto';

    public const AHORRO = 'ahorro';

    public const DEUDA_FIJA = 'deuda_fija';

    /** All categories. */
    public const ALL = [self::INGRESO, self::SERVICIO, self::GASTO, self::AHORRO, self::DEUDA_FIJA];

    /** Egreso categories: everything but ingreso. */
    public const EGRESO = [self::SERVICIO, self::GASTO, self::AHORRO, self::DEUDA_FIJA];
}
