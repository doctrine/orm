<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Persister\Collection;

use Doctrine\ORM\Cache\CacheException;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Utility\StaticClassNameConverter;

class ReadOnlyCachedCollectionPersister extends NonStrictReadWriteCachedCollectionPersister
{
    /**
     * {@inheritdoc}
     */
    public function update(PersistentCollection $collection)
    {
        if ($collection->isDirty() && $collection->getSnapshot()) {
            throw CacheException::updateReadOnlyCollection(
                StaticClassNameConverter::getClass($collection->getOwner()),
                $this->association->getName()
            );
        }

        parent::update($collection);
    }
}
