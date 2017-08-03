<?php


declare(strict_types=1);

namespace Doctrine\ORM\Cache\Persister\Collection;

use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Cache\CacheException;
use Doctrine\Common\Util\ClassUtils;

/**
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 * @since 2.5
 */
class ReadOnlyCachedCollectionPersister extends NonStrictReadWriteCachedCollectionPersister
{
     /**
     * {@inheritdoc}
     */
    public function update(PersistentCollection $collection)
    {
        if ($collection->isDirty() && $collection->getSnapshot()) {
            throw CacheException::updateReadOnlyCollection(
                ClassUtils::getClass($collection->getOwner()),
                $this->association->getName()
            );
        }

        parent::update($collection);
    }
}
