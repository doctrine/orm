<?php declare(strict_types=1);

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

namespace Doctrine\ORM;

/**
 * Provides an API for querying/managing the second level cache regions.
 *
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
interface Cache
{
    const DEFAULT_QUERY_REGION_NAME = 'query_cache_region';

    const DEFAULT_TIMESTAMP_REGION_NAME = 'timestamp_cache_region';

    /**
     * May read items from the cache, but will not add items.
     */
    const MODE_GET = 1;

    /**
     * Will never read items from the cache,
     * but will add items to the cache as it reads them from the database.
     */
    const MODE_PUT = 2;

    /**
     * May read items from the cache, and add items to the cache.
     */
    const MODE_NORMAL = 3;

    /**
     * The query will never read items from the cache,
     * but will refresh items to the cache as it reads them from the database.
     */
    const MODE_REFRESH = 4;

    /**
     * @param string $className The entity class.
     *
     * @return \Doctrine\ORM\Cache\Region|null
     */
    public function getEntityCacheRegion(string $className): ?\Doctrine\ORM\Cache\Region;

    /**
     * @param string $className   The entity class.
     * @param string $association The field name that represents the association.
     *
     * @return \Doctrine\ORM\Cache\Region|null
     */
    public function getCollectionCacheRegion(string $className, string $association): ?\Doctrine\ORM\Cache\Region;

    /**
     * Determine whether the cache contains data for the given entity "instance".
     *
     * @param string $className  The entity class.
     * @param mixed  $identifier The entity identifier
     *
     * @return boolean true if the underlying cache contains corresponding data; false otherwise.
     */
    public function containsEntity(string $className, $identifier): bool;

    /**
     * Evicts the entity data for a particular entity "instance".
     *
     * @param string $className  The entity class.
     * @param mixed  $identifier The entity identifier.
     *
     * @return void
     */
    public function evictEntity(string $className, $identifier): void;

    /**
     * Evicts all entity data from the given region.
     *
     * @param string $className The entity metadata.
     *
     * @return void
     */
    public function evictEntityRegion(string $className): void;

    /**
     * Evict data from all entity regions.
     *
     * @return void
     */
    public function evictEntityRegions(): void;

    /**
     * Determine whether the cache contains data for the given collection.
     *
     * @param string $className       The entity class.
     * @param string $association     The field name that represents the association.
     * @param mixed  $ownerIdentifier The identifier of the owning entity.
     *
     * @return boolean true if the underlying cache contains corresponding data; false otherwise.
     */
    public function containsCollection(string $className, string $association, $ownerIdentifier): bool;

    /**
     * Evicts the cache data for the given identified collection instance.
     *
     * @param string $className       The entity class.
     * @param string $association     The field name that represents the association.
     * @param mixed  $ownerIdentifier The identifier of the owning entity.
     *
     * @return void
     */
    public function evictCollection(string $className, string $association, $ownerIdentifier): void;

    /**
     * Evicts all entity data from the given region.
     *
     * @param string $className   The entity class.
     * @param string $association The field name that represents the association.
     *
     * @return void
     */
    public function evictCollectionRegion(string $className, string $association): void;

    /**
     * Evict data from all collection regions.
     *
     * @return void
     */
    public function evictCollectionRegions(): void;

    /**
     * Determine whether the cache contains data for the given query.
     *
     * @param string $regionName The cache name given to the query.
     *
     * @return boolean true if the underlying cache contains corresponding data; false otherwise.
     */
    public function containsQuery(string $regionName): bool;

    /**
     * Evicts all cached query results under the given name, or default query cache if the region name is NULL.
     *
     * @param string|null $regionName The cache name associated to the queries being cached.
     */
    public function evictQueryRegion(?string $regionName = null): void;

    /**
     * Evict data from all query regions.
     *
     * @return void
     */
    public function evictQueryRegions(): void;

    /**
     * Get query cache by region name or create a new one if none exist.
     *
     * @param string|null $regionName Query cache region name, or default query cache if the region name is NULL.
     *
     * @return \Doctrine\ORM\Cache\QueryCache The Query Cache associated with the region name.
     */
    public function getQueryCache(?string $regionName = null): \Doctrine\ORM\Cache\QueryCache;
}
