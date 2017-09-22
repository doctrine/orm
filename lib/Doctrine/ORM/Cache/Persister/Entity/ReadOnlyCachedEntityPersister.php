<?php


declare(strict_types=1);

namespace Doctrine\ORM\Cache\Persister\Entity;

use Doctrine\ORM\Cache\CacheException;
use Doctrine\ORM\Utility\StaticClassNameConverter;

/**
 * Specific read-only region entity persister
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 * @since 2.5
 */
class ReadOnlyCachedEntityPersister extends NonStrictReadWriteCachedEntityPersister
{
    /**
     * {@inheritdoc}
     */
    public function update($entity)
    {
        throw CacheException::updateReadOnlyEntity(StaticClassNameConverter::getClass($entity));
    }
}
