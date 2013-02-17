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
 * Defines contract for concurrently managed data region.
 *
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
interface ConcurrentRegion extends Region
{
    /**
     * Attempts to write lock the mapping for the given key.
     *
     * @param \Doctrine\ORM\Cache\CacheKey $key The key of the item to lock.
     *
     * @return string A lock identifier
     *
     * @throws \Doctrine\ORM\Cache\LockException if the lock already exists.
     */
    public function writeLock($key);

    /**
     * Attempts to write unlock the mapping for the given key.
     *
     * @param \Doctrine\ORM\Cache\CacheKey $key  The key of the item to unlock.
     * @param string                       $lock The lock identifier previously obtained from {@link writeLock}
     *
     * @throws \Doctrine\ORM\Cache\LockException
     */
    public function writeUnlock($key, $lock);

    /**
     * Attempts to read lock the mapping for the given key.
     *
     * @param \Doctrine\ORM\Cache\CacheKey $key The key of the item to lock.
     *
     * @return string A lock identifier.
     *
     * @throws \Doctrine\ORM\Cache\LockException if the lock already exists.
     */
    public function readLock($key);

    /**
     * Attempts to read unlock the mapping for the given key.
     *
     * @param \Doctrine\ORM\Cache\CacheKey $key  The key of the item to unlock.
     * @param string                       $lock The lock identifier previously obtained from {@link writeLock}
     *
     * @throws \Doctrine\ORM\Cache\LockException
     */
    public function readUnlock($key, $lock);
}
