<?php

namespace App\Constant;

final class SpaceFrequencyConstant
{
    public const WEEKLY = 'weekly';

    public const BIWEEKLY = 'biweekly';

    public const MONTHLY = 'monthly';

    public const CUSTOM = 'custom';

    public const ALL = [self::WEEKLY, self::BIWEEKLY, self::MONTHLY, self::CUSTOM];
}
