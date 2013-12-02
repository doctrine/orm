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
use Doctrine\ORM\Cache\CacheKey;
use Doctrine\ORM\Cache\CacheEntry;
use Doctrine\Common\Cache\CacheProvider;

/**
 * The simplest cache region compatible with all doctrine-cache drivers.
 *
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class DefaultRegion implements Region
{
    /**
     * @var \Doctrine\Common\Cache\CacheProvider
     */
    protected $cache;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var integer
     */
    protected $lifetime = 0;

    /**
     * @param string                                $name
     * @param \Doctrine\Common\Cache\CacheProvider  $cache
     * @param integer                               $lifetime
     */
    public function __construct($name, CacheProvider $cache, $lifetime = 0)
    {
        $this->cache    = $cache;
        $this->name     = (string) $name;
        $this->lifetime = (integer) $lifetime;

        $this->cache->setNamespace($this->name);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return \Doctrine\Common\Cache\CacheProvider
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
        return $this->cache->contains($this->name . '_' . $key->hash);
    }

    /**
     * {@inheritdoc}
     */
    public function get(CacheKey $key)
    {
        return $this->cache->fetch($this->name . '_' . $key->hash) ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function put(CacheKey $key, CacheEntry $entry, Lock $lock = null)
    {
        return $this->cache->save($this->name . '_' . $key->hash, $entry, $this->lifetime);
    }

    /**
     * {@inheritdoc}
     */
    public function evict(CacheKey $key)
    {
        return $this->cache->delete($this->name . '_' . $key->hash);
    }

    /**
     * {@inheritdoc}
     */
    public function evictAll()
    {
        return $this->cache->deleteAll();
    }
}
