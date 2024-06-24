<?php

declare(strict_types=1);

namespace Doctrine\ORM\Persisters\Traits;

use BackedEnum;
use Doctrine\ORM\Proxy\DefaultProxyClassNameResolver;

use function array_merge;
use function is_array;
use function is_object;

/** @internal */
trait ResolveValuesHelper
{
    /**
     * Retrieves the parameters that identifies a value.
     *
     * @return mixed[]
     */
    private function getValues(mixed $value): array
    {
        if (is_array($value)) {
            $newValues = [];

            foreach ($value as $itemValue) {
                $newValues[] = $this->getValues($itemValue);
            }

            return [array_merge(...$newValues)];
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
            $newValues = [];

            foreach ($class->getIdentifierValues($value) as $innerValue) {
                $newValues[] = $this->getValues($innerValue);
            }

            return array_merge(...$newValues);
        }

        return [$this->em->getUnitOfWork()->getSingleIdentifierValue($value)];
    }
}
