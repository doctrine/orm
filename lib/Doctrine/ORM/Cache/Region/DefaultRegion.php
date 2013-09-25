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

use Doctrine\ORM\Cache\Lock;
use Doctrine\ORM\Cache\Region;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Cache\CacheKey;
use Doctrine\ORM\Cache\CacheEntry;

/**
 * The simplest cache region compatible with all doctrine-cache drivers.
 *
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class DefaultRegion implements Region
{
    const ENTRY_KEY = '_entry_';

    /**
     * @var \Doctrine\Common\Cache\Cache
     */
    private $cache;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $mapKey;

    /**
     * @var integer
     */
    private $lifetime = 0;

    /**
     * @param string                       $name
     * @param \Doctrine\Common\Cache\Cache $cache
     * @param array                        $configuration
     */
    public function __construct($name, Cache $cache, array $configuration = array())
    {
        $this->name   = $name;
        $this->cache  = $cache;
        $this->mapKey = "{$this->name}_map";

        if (isset($configuration['lifetime']) && $configuration['lifetime'] > 0) {
            $this->lifetime = (integer) $configuration['lifetime'];
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
     * @return \Doctrine\Common\Cache\Cache
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * {@inheritdoc}
     */
    public function contains(CacheKey $key)
    {
        return $this->cache->contains($this->name . self::ENTRY_KEY . $key->hash);
    }

    /**
     * {@inheritdoc}
     */
    public function get(CacheKey $key)
    {
        return $this->cache->fetch($this->name . self::ENTRY_KEY . $key->hash) ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function put(CacheKey $key, CacheEntry $entry, Lock $lock = null)
    {
        $entryKey   = $this->name . self::ENTRY_KEY . $key->hash;
        $entries    = $this->cache->fetch($this->mapKey);

        $entries[$entryKey] = 1;

        if ($this->cache->save($entryKey, $entry, $this->lifetime)) {
            $this->cache->save($this->mapKey, $entries);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function evict(CacheKey $key)
    {
        $entryKey   = $this->name . self::ENTRY_KEY . $key->hash;
        $entries    = $this->cache->fetch($this->mapKey);

        if ($this->cache->delete($entryKey)) {

            unset($entries[$entryKey]);

            $this->cache->save($this->mapKey, $entries);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function evictAll()
    {
        $entries = $this->cache->fetch($this->mapKey);

        if ( ! is_array($entries) || empty($entries)) {
            return true;
        }

        foreach ($entries as $entryKey => $value) {
            if ($this->cache->delete($entryKey)) {
                unset($entries[$entryKey]);
            }
        }

        $this->cache->save($this->mapKey, $entries);

        return empty($entries);
    }
}
