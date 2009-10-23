<?php
/*
 *  $Id$
 *
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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Common\Cache;

/**
 * Base class for cache driver implementations.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision: 3938 $
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
abstract class AbstractCache implements Cache
{
    /** @var string The cache id to store the index of cache ids under */
    private $_cacheIdsIndexId = 'doctrine_cache_ids';

    /** @var string The namespace to prefix all cache ids with */
    private $_namespace = null;
    
    /** @var boolean Whether to manage cache keys or not. */
    private $_manageCacheKeys = false;
    
    /**
     * Sets whether cache keys should be managed by the cache driver
     * separately from the cache entries. This allows more granular
     * cache clearing through {@link deleteByPrefix}, {@link deleteByRegex},
     * {@link deleteBySuffix} and some other operations such as {@link count}
     * and {@link getIds}. Managing cache keys comes at the cost of a higher
     * probability for cache slams due to the single cache key used for
     * managing all other keys.
     * 
     * @param boolean $bool
     */
    public function setManageCacheKeys($bool)
    {
        $this->_manageCacheKeys = $bool;
    }
    
    /**
     * Checks whether cache keys are managed by this cache driver.
     * 
     * @return boolean
     * @see setManageCacheKeys()
     */
    public function getManageCacheKeys()
    {
        return $this->_manageCacheKeys;
    }

    /**
     * Set the namespace to prefix all cache ids with.
     *
     * @param string $namespace
     * @return void
     */
    public function setNamespace($namespace)
    {
        $this->_namespace = $namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($id)
    {
        return $this->_doFetch($this->_getNamespacedId($id));
    }

    /**
     * {@inheritdoc}
     */
    public function contains($id)
    {
        return $this->_doContains($this->_getNamespacedId($id));
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $lifeTime = false)
    {
        $id = $this->_getNamespacedId($id);
        if ($this->_doSave($id, $data, $lifeTime)) {
            if ($this->_manageCacheKeys) {
                $this->_saveId($id);
            }

            return true;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        $id = $this->_getNamespacedId($id);

        if (strpos($id, '*') !== false) {
            return $this->deleteByRegex('/' . str_replace('*', '.*', $id) . '/');
        }

        if ($this->_doDelete($id)) {
            if ($this->_manageCacheKeys) {
                $this->_deleteId($id);
            }

            return true;
        }
        return false;
    }

    /**
     * Delete all cache entries.
     *
     * @return array $deleted  Array of the deleted cache ids
     */
    public function deleteAll()
    {
        $this->_errorIfCacheKeysNotManaged();
        $ids = $this->getIds();
        foreach ($ids as $id) {
            $this->delete($id);
        }
        return $ids;
    }

    /**
     * Delete cache entries where the id matches a PHP regular expressions
     *
     * @param string $regex
     * @return array $deleted  Array of the deleted cache ids
     */
    public function deleteByRegex($regex)
    {
        $this->_errorIfCacheKeysNotManaged();
        $deleted = array();
        $ids = $this->getIds();
        foreach ($ids as $id) {
            if (preg_match($regex, $id)) {
                $this->delete($id);
                $deleted[] = $id;
            }
        }
        return $deleted;
    }

    /**
     * Delete cache entries where the id has the passed prefix
     *
     * @param string $prefix
     * @return array $deleted  Array of the deleted cache ids
     */
    public function deleteByPrefix($prefix)
    {
        $this->_errorIfCacheKeysNotManaged();
        $deleted = array();
        $ids = $this->getIds();
        foreach ($ids as $id) {
            if (strpos($id, $prefix) == 0) {
                $this->delete($id);
                $deleted[] = $id;
            }
        }
        return $deleted;
    }

    /**
     * Delete cache entries where the id has the passed suffix
     *
     * @param string $suffix 
     * @return array $deleted  Array of the deleted cache ids
     */
    public function deleteBySuffix($suffix)
    {
        $this->_errorIfCacheKeysNotManaged();
        $deleted = array();
        $ids = $this->getIds();
        foreach ($ids as $id) {
            if (substr($id, -1 * strlen($suffix)) == $suffix) {
                $this->delete($id);
                $deleted[] = $id;
            }
        }
        return $deleted;
    }

    /**
     * Count and return the number of cache entries.
     *
     * @return integer $count
     */
    public function count() 
    {
        $this->_errorIfCacheKeysNotManaged();
        $ids = $this->getIds();
        return $ids ? count($ids) : 0;
    }

    /**
     * Get an array of all the cache ids stored
     *
     * @return array $ids
     */
    public function getIds()
    {
        $this->_errorIfCacheKeysNotManaged();
        $ids = $this->fetch($this->_cacheIdsIndexId);
        return $ids ? $ids : array();
    }

    /**
     * Prefix the passed id with the configured namespace value
     *
     * @param string $id  The id to namespace
     * @return string $id The namespaced id
     */
    private function _getNamespacedId($id)
    {
        if ( ! $this->_namespace || strpos($id, $this->_namespace) === 0) {
            return $id;
        } else {
            return $this->_namespace . $id;
        }
    }

    /**
     * Save a cache id to the index of cache ids
     *
     * @param string $id
     * @return boolean TRUE if the id was successfully stored in the cache, FALSE otherwise.
     */
    private function _saveId($id)
    {
        $ids = $this->getIds();
        $ids[] = $id;

        $cacheIdsIndexId = $this->_getNamespacedId($this->_cacheIdsIndexId);
        return $this->_doSave($cacheIdsIndexId, $ids, null);
    }

    /**
     * Delete a cache id from the index of cache ids
     *
     * @param string $id 
     * @return boolean TRUE if the entry was successfully removed from the cache, FALSE otherwise.
     */
    private function _deleteId($id)
    {
        $ids = $this->getIds();
        $key = array_search($id, $ids);
        if ($key !== false) {
            unset($ids[$key]);

            $cacheIdsIndexId = $this->_getNamespacedId($this->_cacheIdsIndexId);
            return $this->_doSave($cacheIdsIndexId, $ids, null);
        }
        return false;
    }
    
    /**
     * @throws BadMethodCallException If the cache driver does not manage cache keys.
     */
    private function _errorIfCacheKeysNotManaged()
    {
        if ( ! $this->_manageCacheKeys) {
            throw new \BadMethodCallException("Operation not supported if cache keys are not managed.");
        }
    }

    /**
     * Fetches an entry from the cache.
     * 
     * @param string $id cache id The id of the cache entry to fetch.
     * @return string The cached data or FALSE, if no cache entry exists for the given id.
     */
    abstract protected function _doFetch($id);

    /**
     * Test if an entry exists in the cache.
     *
     * @param string $id cache id The cache id of the entry to check for.
     * @return boolean TRUE if a cache entry exists for the given cache id, FALSE otherwise.
     */
    abstract protected function _doContains($id);

    /**
     * Puts data into the cache.
     *
     * @param string $id The cache id.
     * @param string $data The cache entry/data.
     * @param int $lifeTime The lifetime. If != false, sets a specific lifetime for this cache entry (null => infinite lifeTime).
     * @return boolean TRUE if the entry was successfully stored in the cache, FALSE otherwise.
     */
    abstract protected function _doSave($id, $data, $lifeTime = false);

    /**
     * Deletes a cache entry.
     * 
     * @param string $id cache id
     * @return boolean TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    abstract protected function _doDelete($id);
}