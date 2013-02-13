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

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Cache\Region\DefaultRegion;
use Doctrine\ORM\Cache\Access\ReadOnlyRegionAccess;
use Doctrine\ORM\Cache\Access\NonStrictReadWriteRegionAccessStrategy;

/**
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class CacheAccessProvider implements AccessProvider
{
    /**
     * @var \Doctrine\Common\Cache\Cache
     */
    private $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function buildEntityRegionAccessStrategy(ClassMetadata $metadata)
    {
        $properties = $metadata->cache['properties'];
        $regionName = $metadata->cache['region'];
        $usage      = $metadata->cache['usage'];

        if ($usage === ClassMetadata::CACHE_USAGE_READ_ONLY) {
            return new ReadOnlyRegionAccess(new DefaultRegion($regionName, $this->cache, $properties));
        }

        if ($usage === ClassMetadata::CACHE_USAGE_NONSTRICT_READ_WRITE) {
            return new NonStrictReadWriteRegionAccessStrategy(new DefaultRegion($regionName, $this->cache, $properties));
        }

        throw new \InvalidArgumentException(sprintf("Unrecognized access strategy type [%s]", $usage));
    }

    /**
     * {@inheritdoc}
     */
    public function buildCollectioRegionAccessStrategy(ClassMetadata $metadata, $fieldName)
    {
        $mapping    = $metadata->getAssociationMapping($fieldName);
        $properties = $mapping['cache']['properties'];
        $regionName = $mapping['cache']['region'];
        $usage      = $mapping['cache']['usage'];

        if ($usage === ClassMetadata::CACHE_USAGE_READ_ONLY) {
            return new ReadOnlyRegionAccess(new DefaultRegion($regionName, $this->cache, $properties));
        }

        if ($usage === ClassMetadata::CACHE_USAGE_NONSTRICT_READ_WRITE) {
            return new NonStrictReadWriteRegionAccessStrategy(new DefaultRegion($regionName, $this->cache, $properties));
        }

        throw new \InvalidArgumentException(sprintf("Unrecognized access strategy type [%s]", $usage));
    }
}
