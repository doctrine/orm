<?php

declare(strict_types=1);

namespace Doctrine\ORM\Persisters\Traits;

use BackedEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Proxy\DefaultProxyClassNameResolver;

use function array_merge;
use function is_array;
use function is_object;

trait ResolveValuesHelper
{
    protected EntityManagerInterface $em;

    /**
     * Retrieves the parameters that identifies a value.
     *
     * @return mixed[]
     */
    private function getValues(mixed $value): array
    {
        if (is_array($value)) {
            $newValue = [];

            foreach ($value as $itemValue) {
                $newValue = array_merge($newValue, $this->getValues($itemValue));
            }

            return [$newValue];
        }

        return $this->getIndividualValue($value);
    }

    /**
     * Retrieves an individual parameter value.
     *
     * @psalm-return list<mixed>
     */
    private function getIndividualValue(mixed $value): array
    {
        if (! is_object($value)) {
            return [$value];
        }

        if ($value instanceof BackedEnum) {
            return [$value->value];
        }

        $valueClass = DefaultProxyClassNameResolver::getClass($value);

        if ($this->em->getMetadataFactory()->isTransient($valueClass)) {
            return [$value];
        }

        $class = $this->em->getClassMetadata($valueClass);

        if ($class->isIdentifierComposite) {
            $newValue = [];

            foreach ($class->getIdentifierValues($value) as $innerValue) {
                $newValue = array_merge($newValue, $this->getValues($innerValue));
            }

            return $newValue;
        }

        return [$this->em->getUnitOfWork()->getSingleIdentifierValue($value)];
    }
}
