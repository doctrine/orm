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
use Doctrine\ORM\Cache\Access\ReadOnlyRegionAccess;
use Doctrine\ORM\Cache\Access\NonStrictReadWriteRegionAccessStrategy;

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

    public function __construct(Configuration $configuration, CacheDriver $cache)
    {
        $this->cache         = $cache;
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function buildEntityRegionAccessStrategy(ClassMetadata $metadata)
    {
        $regionName = $metadata->cache['region'];
        $usage      = $metadata->cache['usage'];

        if ($usage === ClassMetadata::CACHE_USAGE_READ_ONLY) {
            return new ReadOnlyRegionAccess($this->createRegion($regionName));
        }

        if ($usage === ClassMetadata::CACHE_USAGE_NONSTRICT_READ_WRITE) {
            return new NonStrictReadWriteRegionAccessStrategy($this->createRegion($regionName));
        }

        throw new \InvalidArgumentException(sprintf("Unrecognized access strategy type [%s]", $usage));
    }

    /**
     * {@inheritdoc}
     */
    public function buildCollectionRegionAccessStrategy(ClassMetadata $metadata, $fieldName)
    {
        $mapping    = $metadata->getAssociationMapping($fieldName);
        $regionName = $mapping['cache']['region'];
        $usage      = $mapping['cache']['usage'];

        if ($usage === ClassMetadata::CACHE_USAGE_READ_ONLY) {
            return new ReadOnlyRegionAccess($this->createRegion($regionName));
        }

        if ($usage === ClassMetadata::CACHE_USAGE_NONSTRICT_READ_WRITE) {
            return new NonStrictReadWriteRegionAccessStrategy($this->createRegion($regionName));
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
    public function buildCollectionEntryStructure(EntityManagerInterface $em)
    {
        return new DefaultCollectionEntryStructure($em);
    }

    /**
     * {@inheritdoc}
     */
    public function buildEntityEntryStructure(EntityManagerInterface $em)
    {
        return new DefaultEntityEntryStructure($em);
    }

    /**
     * @param string $regionName
     * @return \Doctrine\ORM\Cache\Region\DefaultRegion
     */
    public function createRegion($regionName)
    {
        return new DefaultRegion($regionName, $this->cache, array(
            'lifetime' => $this->configuration->getSecondLevelCacheRegionLifetime($regionName)
        ));
    }

}