<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\Hydration;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\InvalidHydrationMode;
use Doctrine\ORM\Query;

final class DefaultHydratorFactory implements HydratorFactory
{
    public function create(EntityManagerInterface $em, Configuration $config, string|int $hydrationMode): AbstractHydrator
    {
        return match ($hydrationMode) {
            Query::HYDRATE_OBJECT => new ObjectHydrator($em),
            Query::HYDRATE_ARRAY => new ArrayHydrator($em),
            Query::HYDRATE_SCALAR => new ScalarHydrator($em),
            Query::HYDRATE_SINGLE_SCALAR => new SingleScalarHydrator($em),
            Query::HYDRATE_SIMPLEOBJECT => new SimpleObjectHydrator($em),
            Query::HYDRATE_SCALAR_COLUMN => new ScalarColumnHydrator($em),
            default => $this->createCustomHydrator((string) $hydrationMode, $em, $config),
        };
    }

    private function createCustomHydrator(string $hydrationMode, EntityManagerInterface $em, Configuration $config): AbstractHydrator
    {
        $class = $config->getCustomHydrationMode($hydrationMode);

        if ($class !== null) {
            return new $class($em);
        }

        throw InvalidHydrationMode::fromMode($hydrationMode);
    }
}
