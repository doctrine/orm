<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Persister\Collection;

use Doctrine\ORM\Cache\Exception\CannotUpdateReadOnlyCollection;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Proxy\DefaultProxyClassNameResolver;

class ReadOnlyCachedCollectionPersister extends NonStrictReadWriteCachedCollectionPersister
{
     /**
      * {@inheritDoc}
      */
    public function update(PersistentCollection $collection)
    {
        if ($collection->isDirty() && $collection->getSnapshot()) {
            throw CannotUpdateReadOnlyCollection::fromEntityAndField(
                DefaultProxyClassNameResolver::getClass($collection->getOwner()),
                $this->association['fieldName']
            );
        }

        parent::update($collection);
    }
}
