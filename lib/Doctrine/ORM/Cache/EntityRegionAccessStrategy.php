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
 * Interface for entity region access.
 *
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
interface EntityRegionAccessStrategy extends RegionAccessStrategy
{
    /**
     * Called after an item has been inserted (after the transaction completes).
     *
     * @param \Doctrine\ORM\Cache\CacheKey      $key    The cache key.
     * @param \Doctrine\ORM\Cache\CacheEntry    $entry  The cache entry.
     *
     * @return boolean TRUE If the contents of the cache actual were changed.
     *
     * @throws \Doctrine\ORM\Cache\CacheException
     */
    public function afterInsert(CacheKey $key, CacheEntry $entry);

    /**
     * Called after an item has been updated (after the transaction completes).
     *
     * @param \Doctrine\ORM\Cache\CacheKey      $key    The cache key.
     * @param \Doctrine\ORM\Cache\CacheEntry    $entry  The cache entry.
     * @param \Doctrine\ORM\Cache\Lock          $lock   The lock previously obtained from {@link lockItem}
     *
     * @return boolean TRUE If the contents of the cache actual were changed.
     *
     * @throws \Doctrine\ORM\Cache\CacheException
     */
    public function afterUpdate(CacheKey $key, CacheEntry $entry, Lock $lock = null);
}
