<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use Doctrine\Common\Cache\Cache as CacheAdapter;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\MultiGetCache;
use Doctrine\ORM\Cache;
use Doctrine\ORM\Cache\Persister\Collection\NonStrictReadWriteCachedCollectionPersister;
use Doctrine\ORM\Cache\Persister\Collection\ReadOnlyCachedCollectionPersister;
use Doctrine\ORM\Cache\Persister\Collection\ReadWriteCachedCollectionPersister;
use Doctrine\ORM\Cache\Persister\Entity\NonStrictReadWriteCachedEntityPersister;
use Doctrine\ORM\Cache\Persister\Entity\ReadOnlyCachedEntityPersister;
use Doctrine\ORM\Cache\Persister\Entity\ReadWriteCachedEntityPersister;
use Doctrine\ORM\Cache\Region\DefaultMultiGetRegion;
use Doctrine\ORM\Cache\Region\DefaultRegion;
use Doctrine\ORM\Cache\Region\FileLockRegion;
use Doctrine\ORM\Cache\Region\UpdateTimestampCache;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\AssociationMetadata;
use Doctrine\ORM\Mapping\CacheMetadata;
use Doctrine\ORM\Mapping\CacheUsage;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Collection\CollectionPersister;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use InvalidArgumentException;
use LogicException;
use const DIRECTORY_SEPARATOR;
use function sprintf;

class DefaultCacheFactory implements CacheFactory
{
    /** @var CacheAdapter */
    private $cache;

    /** @var RegionsConfiguration */
    private $regionsConfig;

    /** @var TimestampRegion|null */
    private $timestampRegion;

    /** @var Region[] */
    private $regions = [];

    /** @var string|null */
    private $fileLockRegionDirectory;

    public function __construct(RegionsConfiguration $cacheConfig, CacheAdapter $cache)
    {
        $this->regionsConfig = $cacheConfig;
        $this->cache         = $cache;
    }

    /**
     * @param string $fileLockRegionDirectory
     */
    public function setFileLockRegionDirectory($fileLockRegionDirectory)
    {
        $this->fileLockRegionDirectory = (string) $fileLockRegionDirectory;
    }

    /**
     * @return string
     */
    public function getFileLockRegionDirectory()
    {
        return $this->fileLockRegionDirectory;
    }

    public function setRegion(Region $region)
    {
        $this->regions[$region->getName()] = $region;
    }

    public function setTimestampRegion(TimestampRegion $region)
    {
        $this->timestampRegion = $region;
    }

    /**
     * {@inheritdoc}
     */
    public function buildCachedEntityPersister(
        EntityManagerInterface $em,
        EntityPersister $persister,
        ClassMetadata $metadata
    ) {
        $cache  = $metadata->getCache();
        $region = $this->getRegion($cache);
        $usage  = $cache->getUsage();

        switch ($usage) {
            case CacheUsage::READ_ONLY:
                return new ReadOnlyCachedEntityPersister($persister, $region, $em, $metadata);

            case CacheUsage::READ_WRITE:
                return new ReadWriteCachedEntityPersister($persister, $region, $em, $metadata);

            case CacheUsage::NONSTRICT_READ_WRITE:
                return new NonStrictReadWriteCachedEntityPersister($persister, $region, $em, $metadata);

            default:
                throw new InvalidArgumentException(sprintf('Unrecognized access strategy type [%s]', $usage));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildCachedCollectionPersister(
        EntityManagerInterface $em,
        CollectionPersister $persister,
        AssociationMetadata $association
    ) {
        $cache  = $association->getCache();
        $region = $this->getRegion($cache);
        $usage  = $cache->getUsage();

        switch ($usage) {
            case CacheUsage::READ_ONLY:
                return new ReadOnlyCachedCollectionPersister($persister, $region, $em, $association);

            case CacheUsage::READ_WRITE:
                return new ReadWriteCachedCollectionPersister($persister, $region, $em, $association);

            case CacheUsage::NONSTRICT_READ_WRITE:
                return new NonStrictReadWriteCachedCollectionPersister($persister, $region, $em, $association);

            default:
                throw new InvalidArgumentException(
                    sprintf('Unrecognized access strategy type [%s]', $usage)
                );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildQueryCache(EntityManagerInterface $em, $regionName = null)
    {
        $cacheMetadata = new CacheMetadata(
            CacheUsage::NONSTRICT_READ_WRITE,
            $regionName ?: Cache::DEFAULT_QUERY_REGION_NAME
        );

        return new DefaultQueryCache($em, $this->getRegion($cacheMetadata));
    }

    /**
     * {@inheritdoc}
     */
    public function buildCollectionHydrator(EntityManagerInterface $em, AssociationMetadata $association)
    {
        return new DefaultCollectionHydrator($em);
    }

    /**
     * {@inheritdoc}
     */
    public function buildEntityHydrator(EntityManagerInterface $em, ClassMetadata $metadata)
    {
        return new DefaultEntityHydrator($em);
    }

    /**
     * {@inheritdoc}
     */
    public function getRegion(CacheMetadata $cache)
    {
        $regionName = $cache->getRegion();

        if (isset($this->regions[$regionName])) {
            return $this->regions[$regionName];
        }

        $cacheAdapter = $this->createRegionCache($regionName);
        $lifetime     = $this->regionsConfig->getLifetime($regionName);
        $region       = $cacheAdapter instanceof MultiGetCache
            ? new DefaultMultiGetRegion($regionName, $cacheAdapter, $lifetime)
            : new DefaultRegion($regionName, $cacheAdapter, $lifetime);

        if ($cache->getUsage() === CacheUsage::READ_WRITE) {
            if (! $this->fileLockRegionDirectory) {
                throw new LogicException(
                    'If you want to use a "READ_WRITE" cache an implementation of "Doctrine\ORM\Cache\ConcurrentRegion" is required, ' .
                    'The default implementation provided by doctrine is "Doctrine\ORM\Cache\Region\FileLockRegion" if you want to use it please provide a valid directory, DefaultCacheFactory#setFileLockRegionDirectory(). '
                );
            }

            $directory = $this->fileLockRegionDirectory . DIRECTORY_SEPARATOR . $regionName;
            $region    = new FileLockRegion($region, $directory, $this->regionsConfig->getLockLifetime($regionName));
        }

        return $this->regions[$regionName] = $region;
    }

    /**
     * @param string $name
     *
     * @return CacheAdapter
     */
    private function createRegionCache($name)
    {
        $cacheAdapter = clone $this->cache;

        if (! $cacheAdapter instanceof CacheProvider) {
            return $cacheAdapter;
        }

        $namespace = $cacheAdapter->getNamespace();

        if ($namespace !== '') {
            $namespace .= ':';
        }

        $cacheAdapter->setNamespace($namespace . $name);

        return $cacheAdapter;
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestampRegion()
    {
        if ($this->timestampRegion === null) {
            $name     = Cache::DEFAULT_TIMESTAMP_REGION_NAME;
            $lifetime = $this->regionsConfig->getLifetime($name);

            $this->timestampRegion = new UpdateTimestampCache($name, clone $this->cache, $lifetime);
        }

        return $this->timestampRegion;
    }

    /**
     * {@inheritdoc}
     */
    public function createCache(EntityManagerInterface $em)
    {
        return new DefaultCache($em);
    }
}
