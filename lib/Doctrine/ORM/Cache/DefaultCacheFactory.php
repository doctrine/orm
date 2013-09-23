<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Cache;

use Doctrine\ORM\Cache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Cache\Region\DefaultRegion;
use Doctrine\Common\Cache\Cache as CacheDriver;

use Doctrine\ORM\Persisters\EntityPersister;
use Doctrine\ORM\Persisters\CollectionPersister;
use Doctrine\ORM\Cache\Persister\ReadOnlyCachedEntityPersister;
use Doctrine\ORM\Cache\Persister\ReadWriteCachedEntityPersister;
use Doctrine\ORM\Cache\Persister\ReadOnlyCachedCollectionPersister;
use Doctrine\ORM\Cache\Persister\ReadWriteCachedCollectionPersister;
use Doctrine\ORM\Cache\Persister\NonStrictReadWriteCachedEntityPersister;
use Doctrine\ORM\Cache\Persister\NonStrictReadWriteCachedCollectionPersister;

/**
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class DefaultCacheFactory implements CacheFactory
{
    /**
     * @var \Doctrine\Common\Cache\Cache
     */
    private $cache;

    /**
     * @var \Doctrine\ORM\Configuration
     */
    private $configuration;

    /**
     * @param \Doctrine\ORM\Configuration  $configuration
     * @param \Doctrine\Common\Cache\Cache $cache
     */
    public function __construct(Configuration $configuration, CacheDriver $cache)
    {
        $this->cache         = $cache;
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function buildCachedEntityPersister(EntityManagerInterface $em, EntityPersister $persister, ClassMetadata $metadata)
    {
        $regionName = $metadata->cache['region'];
        $usage      = $metadata->cache['usage'];
        $region     = $this->createRegion($regionName);

        if ($usage === ClassMetadata::CACHE_USAGE_READ_ONLY) {
            return new ReadOnlyCachedEntityPersister($persister, $region, $em, $metadata);
        }

        if ($usage === ClassMetadata::CACHE_USAGE_NONSTRICT_READ_WRITE) {
            return new NonStrictReadWriteCachedEntityPersister($persister, $region, $em, $metadata);
        }

        if ($usage === ClassMetadata::CACHE_USAGE_READ_WRITE) {
            return new ReadWriteCachedEntityPersister($persister, $region, $em, $metadata);
        }

        throw new \InvalidArgumentException(sprintf("Unrecognized access strategy type [%s]", $usage));
    }

    /**
     * {@inheritdoc}
     */
    public function buildCachedCollectionPersister(EntityManagerInterface $em, CollectionPersister $persister, $mapping)
    {
        $regionName = $mapping['cache']['region'];
        $usage      = $mapping['cache']['usage'];
        $region     = $this->createRegion($regionName);

        if ($usage === ClassMetadata::CACHE_USAGE_READ_ONLY) {
            return new ReadOnlyCachedCollectionPersister($persister, $region, $em, $mapping);
        }

        if ($usage === ClassMetadata::CACHE_USAGE_NONSTRICT_READ_WRITE) {
            return new NonStrictReadWriteCachedCollectionPersister($persister, $region, $em, $mapping);
        }

        if ($usage === ClassMetadata::CACHE_USAGE_NONSTRICT_READ_WRITE) {
            return new ReadWriteCachedCollectionPersister($persister, $region, $em, $mapping);
        }

        throw new \InvalidArgumentException(sprintf("Unrecognized access strategy type [%s]", $usage));
    }

    /**
     * {@inheritdoc}
     */
    public function buildQueryCache(EntityManagerInterface $em, $regionName = null)
    {
        return new DefaultQueryCache($em, $this->createRegion($regionName ?: Cache::DEFAULT_QUERY_REGION_NAME));
    }

    /**
     * {@inheritdoc}
     */
    public function buildCollectionHydrator(EntityManagerInterface $em)
    {
        return new DefaultCollectionHydrator($em);
    }

    /**
     * {@inheritdoc}
     */
    public function buildEntityHydrator(EntityManagerInterface $em)
    {
        return new DefaultEntityHydrator($em);
    }

    /**
     * @param string $regionName
     *
     * @return \Doctrine\ORM\Cache\Region
     */
    protected function createRegion($regionName)
    {
        return new DefaultRegion($regionName, $this->cache, array(
            'lifetime' => $this->configuration->getSecondLevelCacheRegionLifetime($regionName)
        ));
    }
}
