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

namespace Doctrine\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\ORMInvalidArgumentException;

/**
 * Provides an API for querying/managing the second level cache regions.
 *
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class Cache
{

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \Doctrine\ORM\UnitOfWork
     */
    private $uow;

    /**
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em  = $em;
        $this->uow = $this->em->getUnitOfWork();
    }

    /**
     * @param string $className The entity class.
     *
     * @return \Doctrine\ORM\Cache\RegionAccess|null
     */
    public function getEntityCacheRegionAcess($className)
    {
        $metadata  = $this->em->getClassMetadata($className);
        $persister = $this->uow->getEntityPersister($metadata->rootEntityName);

        if ( ! $persister->hasCache()) {
            return null;
        }

        return $persister->getCacheRegionAcess();
    }

    /**
     * @param string $className   The entity class.
     * @param string $association The field name that represents the association.
     *
     * @return \Doctrine\ORM\Cache\RegionAccess|null
     */
    public function getCollectionCacheRegionAcess($className, $association)
    {
        $metadata  = $this->em->getClassMetadata($className);
        $persister = $this->uow->getCollectionPersister($metadata->getAssociationMapping($association));

        if ( ! $persister->hasCache()) {
            return null;
        }

        return $persister->getCacheRegionAcess();
    }

    /**
     * Determine whether the cache contains data for the given entity "instance".
     *
     * @param string $className  The entity class.
     * @param mixed  $identifier The entity identifier
     *
     * @return boolean true if the underlying cache contains corresponding data; false otherwise.
     */
    public function containsEntity($className, $identifier)
    {
        $metadata   = $this->em->getClassMetadata($className);
        $persister  = $this->uow->getEntityPersister($metadata->rootEntityName);
        $key        = $this->buildEntityCacheKey($metadata, $identifier);

        if ( ! $persister->hasCache()) {
            return false;
        }

        return $persister->getCacheRegionAcess()->getRegion()->contains($key);
    }

    /**
     * Evicts the entity data for a particular entity "instance".
     *
     * @param string $className  The entity class.
     * @param mixed  $identifier The entity identifier.
     *
     * @return void
     */
    public function evictEntity($className, $identifier)
    {
        $metadata  = $this->em->getClassMetadata($className);
        $persister = $this->uow->getEntityPersister($metadata->rootEntityName);
        $key       = $this->buildEntityCacheKey($metadata, $identifier);

        if ( ! $persister->hasCache()) {
            return;
        }

        $persister->getCacheRegionAcess()->evict($key);
    }

    /**
     * Evicts all entity data from the given region.
     *
     * @param string $className The entity metadata.
     *
     * @return void
     */
    public function evictEntityRegion($className)
    {
        $metadata  = $this->em->getClassMetadata($className);
        $persister = $this->uow->getEntityPersister($metadata->rootEntityName);

        if ( ! $persister->hasCache()) {
            return;
        }

        $persister->getCacheRegionAcess()->evictAll();
    }

    /**
     * Evict data from all entity regions.
     *
     * @return void
     */
    public function evictEntityRegions()
    {
        $metadatas = $this->em->getMetadataFactory()->getAllMetadata();

        foreach ($metadatas as $metadata) {
            $persister = $this->uow->getEntityPersister($metadata->rootEntityName);

            if ( ! $persister->hasCache()) {
                continue;
            }

            $persister->getCacheRegionAcess()->evictAll();
        }
    }

    /**
     * Determine whether the cache contains data for the given collection.
     *
     * @param string $className       The entity class.
     * @param string $association     The field name that represents the association.
     * @param mixed  $ownerIdentifier The identifier of the owning entity.
     *
     * @return boolean true if the underlying cache contains corresponding data; false otherwise.
     */
    public function containsCollection($className, $association, $ownerIdentifier)
    {
        $metadata  = $this->em->getClassMetadata($className);
        $persister = $this->uow->getCollectionPersister($metadata->getAssociationMapping($association));
        $key       = $this->buildCollectionCacheKey($metadata, $association, $ownerIdentifier);

        if ( ! $persister->hasCache()) {
            return false;
        }

        return $persister->getCacheRegionAcess()->getRegion()->contains($key);
    }

    /**
     * Evicts the cache data for the given identified collection instance.
     *
     * @param string $className       The entity class.
     * @param string $association     The field name that represents the association.
     * @param mixed  $ownerIdentifier The identifier of the owning entity.
     *
     * @return void
     */
    public function evictCollection($className, $association, $ownerIdentifier)
    {
        $metadata  = $this->em->getClassMetadata($className);
        $persister = $this->uow->getCollectionPersister($metadata->getAssociationMapping($association));
        $key       = $this->buildCollectionCacheKey($metadata, $association, $ownerIdentifier);

        if ( ! $persister->hasCache()) {
            return;
        }

        $persister->getCacheRegionAcess()->evict($key);
    }

    /**
     * Evicts all entity data from the given region.
     *
     * @param string $className   The entity class.
     * @param string $association The field name that represents the association.
     *
     * @return void
     */
    public function evictCollectionRegion($className, $association)
    {
        $metadata  = $this->em->getClassMetadata($className);
        $persister = $this->uow->getCollectionPersister($metadata->getAssociationMapping($association));

        if ( ! $persister->hasCache()) {
            return;
        }

        $persister->getCacheRegionAcess()->evictAll();
    }

    /**
     * Evict data from all collection regions.
     *
     * @return void
     */
    public function evictCollectionRegions()
    {
        $metadatas = $this->em->getMetadataFactory()->getAllMetadata();

        foreach ($metadatas as $metadata) {

            foreach ($metadata->associationMappings as $association) {
                if ( ! $association['type'] & ClassMetadata::TO_MANY) {
                    continue;
                }

                $persister = $this->uow->getCollectionPersister($association);

                if ( ! $persister->hasCache()) {
                    return;
                }

                $persister->getCacheRegionAcess()->evictAll();
            }
        }
    }

    /**
     * Determine whether the cache contains data for the given query.
     *
     * @param string $regionName The cache name given to the query.
     *
     * @return boolean true if the underlying cache contains corresponding data; false otherwise.
     */
    public function containsQuery($regionName)
    {
        throw new \BadMethodCallException("Not implemented.");
    }

    /**
     * Evicts all cached query results under the given name.
     *
     * @param string $regionName The cache name associated to the queries being cached.
     */
    public function evictQueryRegion($regionName)
    {
        throw new \BadMethodCallException("Not implemented.");
    }

    /**
     * Evict data from all query regions.
     *
     * @return void
     */
    public function evictQueryRegions()
    {
        throw new \BadMethodCallException("Not implemented.");
    }

    /**
     * Get query cache by region name or create a new one if none exist.
     *
     * @param regionName Query cache region name.
     *
     * @return \Doctrine\ORM\Cache\QueryCache The Query Cache associated with the region name.
     */
    public function getQueryCache($regionName)
    {
        throw new \BadMethodCallException("Not implemented.");
    }

    /**
     * @param \Doctrine\ORM\Mapping\ClassMetadata $metadata   The entity metadata.
     * @param mixed                               $identifier The entity identifier.
     *
     * @return \Doctrine\ORM\Cache\EntityCacheKey
     */
    public function buildEntityCacheKey(ClassMetadata $metadata, $identifier)
    {
        if ( ! is_array($identifier)) {
            $identifier = $this->toIdentifierArray($metadata, $identifier);
        }

        return new EntityCacheKey($metadata->rootEntityName, $identifier);
    }

    /**
     * @param \Doctrine\ORM\Mapping\ClassMetadata $metadata        The entity metadata.
     * @param string                              $association     The field name that represents the association.
     * @param mixed                               $ownerIdentifier The identifier of the owning entity.
     *
     * @return \Doctrine\ORM\Cache\CollectionCacheKey
     */
    public function buildCollectionCacheKey(ClassMetadata $metadata, $association, $ownerIdentifier)
    {
        if ( ! is_array($ownerIdentifier)) {
            $ownerIdentifier = $this->toIdentifierArray($metadata, $ownerIdentifier);;
        }

        return new CollectionCacheKey($metadata->rootEntityName, $association, $ownerIdentifier);
    }

    /**
     * @param \Doctrine\ORM\Mapping\ClassMetadata $metadata   The entity metadata.
     * @param mixed                               $identifier The entity identifier.
     *
     * @return array
     */
    private function toIdentifierArray(ClassMetadata $metadata, $identifier)
    {
        if (is_object($identifier) && $this->em->getMetadataFactory()->hasMetadataFor(ClassUtils::getClass($identifier))) {
            $identifier = $this->unitOfWork->getSingleIdentifierValue($identifier);

            if ($identifier === null) {
                throw ORMInvalidArgumentException::invalidIdentifierBindingEntity();
            }
        }

        return array($metadata->identifier[0] => $identifier);
    }

}
