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

namespace Doctrine\ORM\Cache\Region;

use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\Cache\CacheKey;
use Doctrine\Common\Cache\Cache;

/**
 * Defines a contract for accessing a particular named region.
 *
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class DefaultRegion implements Region
{
    /**
     * @var \Doctrine\Common\Cache\Cache
     */
    private $cache;

    /**
     * @var string
     */
    private $name;

    /**
     * @var integer
     */
    private $lifetime = 0;

    /**
     * @param strgin                       $name
     * @param \Doctrine\Common\Cache\Cache $cache
     * @param array                        $properties
     */
    public function __construct($name, Cache $cache, array $properties = array())
    {
        $this->name   = $name;
        $this->cache  = $cache;

        if (isset($properties['lifetime']) && $properties['lifetime'] > 0) {
            $this->lifetime = (integer) $properties['lifetime'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return \Doctrine\Common\Cache\AccessProvider
     */
    public function getCache()
    {
        return $this->cache;
    }

    private function entryKey(CacheKey $key)
    {
        return sprintf("%s::values[%s]", $this->name, $key->hash());
    }

    private function entriesMapKey()
    {
        return sprintf("%s::entries", $this->name);
    }

    /**
     * {@inheritdoc}
     */
    public function contains(CacheKey $key)
    {
        return $this->cache->contains($this->entryKey($key));
    }

    /**
     * {@inheritdoc}
     */
    public function get(CacheKey $key)
    {
        return $this->cache->fetch($this->entryKey($key)) ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function put(CacheKey $key, array $value)
    {
        $entriesKey = $this->entriesMapKey();
        $entryKey   = $this->entryKey($key);
        $entries    = $this->cache->fetch($entriesKey);

        $entries[$entryKey] = true;

        if ($this->cache->save($entryKey, $value, $this->lifetime)) {
            $this->cache->save($entriesKey, $entries);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function evict(CacheKey $key)
    {
        $entriesKey = $this->entriesMapKey();
        $entryKey   = $this->entryKey($key);
        $entries    = $this->cache->fetch($entriesKey);

        if ($this->cache->delete($entryKey)) {

            unset($entries[$entryKey]);

            $this->cache->save($entriesKey, $entries);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function evictAll()
    {
        $entriesKey = $this->entriesMapKey();
        $entries    = $this->cache->fetch($entriesKey);

        if ( ! is_array($entries) || empty($entries)) {
            return true;
        }

        foreach ($entries as $entryKey => $value) {
            if ($this->cache->delete($entryKey)) {
                unset($entries[$entryKey]);
            }
        }

        $this->cache->save($entriesKey, $entries);

        return empty($entries);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        $entriesKey = $this->entriesMapKey();
        $entries    = $this->cache->fetch($entriesKey);

        if ( ! is_array($entries)) {
            return 0;
        }

        return count($entries);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $data       = array();
        $entriesKey = $this->entriesMapKey();
        $entries    = $this->cache->fetch($entriesKey);

        if ( ! is_array($entries) || empty($entries)) {
            return array();
        }

        foreach ($entries as $entryKey => $value) {
            $data[] = $this->cache->fetch($entryKey);
        }

        return $data;
    }
}
