<?php

namespace App\Constant;

final class InstallmentStatusConstant
{
    public const ACTIVA = 'activa';

    public const POR_TERMINAR = 'por_terminar';

    public const TERMINADA = 'terminada';

    /** An installment with this many or fewer remaining cuotas is "por_terminar". */
    public const POR_TERMINAR_THRESHOLD = 3;
}
