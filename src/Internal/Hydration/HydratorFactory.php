<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\Hydration;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;

interface HydratorFactory
{
    /**
     * Create a new instance for the given hydration mode.
     *
     * @psalm-param string|AbstractQuery::HYDRATE_* $hydrationMode
     */
    public function create(EntityManagerInterface $em, Configuration $config, string|int $hydrationMode): AbstractHydrator;
}
