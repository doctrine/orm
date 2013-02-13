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

/**
 * Region access strategy
 *
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
interface RegionAccess
{
    /**
     * Get the wrapped data cache region
     *
     * @return \Doctrine\ORM\Cache\Region The underlying region
     */
    public function getRegion();

    /**
     * Attempt to retrieve an object from the cache.
     *
     * @param \Doctrine\ORM\Cache\CacheKey $identifier The identifier of the item to be retrieved.
     *
     * @return the cached object or <tt>null</tt>
     *
     * @throws \Doctrine\ORM\Cache\CacheException
     */
    public function get(CacheKey $key);

    /**
     * Attempt to cache an object, after loading from the database.
     *
     * @param \Doctrine\ORM\Cache\CacheKey $identifier The item identifier.
     * @param array                        $value      The item.
     *
     * @return <tt>true</tt> if the object was successfully cached.
     *
     * @throws \Doctrine\ORM\Cache\CacheException
     */
    public function put(CacheKey $key, array $value);

    /**
     * Called after an item has been inserted (after the transaction completes).
     *
     * @param \Doctrine\ORM\Cache\CacheKey $identifier The item identifier.
     * @param array                        $value      The item.
     *
     * @return boolean <tt>true</tt> If the contents of the cache actual were changed.
     *
     * @throws \Doctrine\ORM\Cache\CacheException
     */
    public function afterInsert(CacheKey $key, array $value);

    /**
     * Called after an item has been updated (after the transaction completes).
     *
     * @param \Doctrine\ORM\Cache\CacheKey $identifier The item identifier.
     * @param array                        $value      The item.
     * @param \Doctrine\ORM\Cache\Lock     $lock       The lock previously obtained from {@link lockItem}
     *
     * @return boolean <tt>true</tt> If the contents of the cache actual were changed.
     *
     * @throws \Doctrine\ORM\Cache\CacheException
     */
    public function afterUpdate(CacheKey $key, array $value, Lock $lock = null);

    /**
     * Forcibly evict an item from the cache immediately without regard for locks.
     *
     * @param \Doctrine\ORM\Cache\CacheKey $identifier The identifier of the item to remove.
     *
     * @throws \Doctrine\ORM\Cache\CacheException
     */
    public function evict(CacheKey $key);

    /**
     * Forcibly evict all items from the cache immediately without regard for locks.
     *
     * @throws \Doctrine\ORM\Cache\CacheException
     */
    public function evictAll();
}
