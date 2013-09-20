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

namespace Doctrine\ORM\Cache\Access;

use Doctrine\ORM\Cache\ConcurrentRegionAccessStrategy;
use Doctrine\ORM\Cache\EntityRegionAccessStrategy;
use Doctrine\ORM\Cache\ConcurrentRegion;
use Doctrine\ORM\Cache\CacheException;
use Doctrine\ORM\Cache\LockException;
use Doctrine\ORM\Cache\CacheEntry;
use Doctrine\ORM\Cache\CacheKey;
use Doctrine\ORM\Cache\Lock;

/**
 * Region access strategies for concurrently managed data.
 *
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class ReadWriteEntityRegionAccessStrategy  extends AbstractRegionAccessStrategy implements ConcurrentRegionAccessStrategy, EntityRegionAccessStrategy
{
    /**
     * @param \Doctrine\ORM\Cache\ConcurrentRegion $region
     */
    public function __construct(ConcurrentRegion $region)
    {
        $this->region = $region;
    }

    /**
     * {@inheritdoc}
     */
    public function afterInsert(CacheKey $key, CacheEntry $entry)
    {
        $writeLock = null;

        try {
            if ( ! ($writeLock = $this->region->writeLock($key))) {
                return false;
            }

            if ( ! $this->region->put($key, $entry, $writeLock)) {
                return false;
            }

            $this->region->writeUnlock($key, $writeLock);

            return true;

        } catch (LockException $exc) {

            if ($writeLock) {
                $this->region->writeUnlock($key, $writeLock);
            }

            throw new $exc;
        } catch (\Exception $exc) {

            if ($writeLock) {
                $this->region->writeUnlock($key, $writeLock);
            }

            throw new CacheException($exc->getMessage(), $exc->getCode(), $exc);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function afterUpdate(CacheKey $key, CacheEntry $entry, Lock $readLock = null)
    {
        $writeLock = null;

        try {
            if ( ! ($writeLock = $this->region->writeLock($key, $readLock))) {
                return false;
            }

            if ( ! $this->region->put($key, $entry, $writeLock)) {
                return false;
            }

            $this->region->writeUnlock($key, $writeLock);

            return true;

        } catch (LockException $exc) {

            if ($readLock) {
                $this->region->readUnlock($key, $writeLock);
            }

            if ($writeLock) {
                $this->region->writeUnlock($key, $writeLock);
            }

            throw new $exc;
        } catch (\Exception $exc) {

            if ($readLock) {
                $this->region->readUnlock($key, $writeLock);
            }

            if ($writeLock) {
                $this->region->writeUnlock($key, $writeLock);
            }

            throw new CacheException($exc->getMessage(), $exc->getCode(), $exc);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function lockItem(CacheKey $key)
    {
        return $this->region->readLock($key);
    }

    /**
     * {@inheritdoc}
     */
    public function unlockItem(CacheKey $key, Lock $lock)
    {
        return $this->region->readUnlock($key, $lock);
    }
}
