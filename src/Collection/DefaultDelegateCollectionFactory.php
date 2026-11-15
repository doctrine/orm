<?php

declare(strict_types=1);

namespace Doctrine\ORM\Collection;

use Doctrine\ORM\Exception\UninstantiableCollection;
use ReflectionClass;

/**
 * Calls the constructor without arguments, so that the state it initializes
 * is also available on loaded entities.
 */
final class DefaultDelegateCollectionFactory implements DelegateCollectionFactory
{
    /** @throws UninstantiableCollection When the constructor is not public or has required parameters. */
    public function create(string $collectionClass): DelegateCollection
    {
        $constructor = (new ReflectionClass($collectionClass))->getConstructor();

        if ($constructor !== null && (! $constructor->isPublic() || $constructor->getNumberOfRequiredParameters() > 0)) {
            throw UninstantiableCollection::withoutArguments($collectionClass);
        }

        return new $collectionClass();
    }
}
