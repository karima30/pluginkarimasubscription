<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\State;

interface SubscriptionStates
{
    public const CREATED = 'created';

    public const ON_GOING = 'on_going';

    public const PAUSED = 'paused';

    public const AUTO_PAUSED = 'auto_paused';

    public const FINALIZED = 'finalized';

    public const STOPPED = 'stopped';

}
