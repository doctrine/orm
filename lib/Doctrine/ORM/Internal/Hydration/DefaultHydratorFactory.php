<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\Hydration;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\InvalidHydrationMode;

final class DefaultHydratorFactory implements HydratorFactory
{
    /**
     * {@inheritDoc}
     */
    public function create(EntityManagerInterface $em, Configuration $config, $hydrationMode): AbstractHydrator
    {
        switch ($hydrationMode) {
            case AbstractQuery::HYDRATE_OBJECT:
                return new ObjectHydrator($em);

            case AbstractQuery::HYDRATE_ARRAY:
                return new ArrayHydrator($em);

            case AbstractQuery::HYDRATE_SCALAR:
                return new ScalarHydrator($em);

            case AbstractQuery::HYDRATE_SINGLE_SCALAR:
                return new SingleScalarHydrator($em);

            case AbstractQuery::HYDRATE_SIMPLEOBJECT:
                return new SimpleObjectHydrator($em);

            case AbstractQuery::HYDRATE_SCALAR_COLUMN:
                return new ScalarColumnHydrator($em);

            default:
                $class = $config->getCustomHydrationMode($hydrationMode);

                if ($class !== null) {
                    return new $class($em);
                }
        }

        throw InvalidHydrationMode::fromMode((string) $hydrationMode);
    }
}
