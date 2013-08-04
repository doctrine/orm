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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Cache\Persisters\CachedPersister;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Cache\QueryCacheEntry;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Cache\CacheException;
use Doctrine\Common\Proxy\Proxy;
use Doctrine\ORM\Cache;
use Doctrine\ORM\Query;

/**
 * Default query cache implementation.
 *
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class DefaultQueryCache implements QueryCache
{
     /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \Doctrine\ORM\UnitOfWork
     */
    private $uow;

    /**
     * @var \Doctrine\ORM\Cache\Region
     */
    private $region;

    /**
     * @var \Doctrine\ORM\Cache\QueryCacheValidator
     */
    private $validator;

    /**
     * @var array
     */
    private static $hints = array(Query::HINT_CACHE_ENABLED => true);

    /**
     * @param \Doctrine\ORM\EntityManagerInterface  $em     The entity manager.
     * @param \Doctrine\ORM\Cache\Region            $region The query region.
     */
    public function __construct(EntityManagerInterface $em, Region $region)
    {
        $this->em        = $em;
        $this->region    = $region;
        $this->uow       = $em->getUnitOfWork();
        $this->validator = $em->getConfiguration()->getSecondLevelCacheQueryValidator();
    }

    /**
     * {@inheritdoc}
     *
     * @TODO - does not work recursively yet
     */
    public function get(QueryCacheKey $key, ResultSetMapping $rsm)
    {
        if ( ! ($key->cacheMode & Cache::MODE_GET)) {
            return null;
        }

        $entry = $this->region->get($key);

        if ( ! $entry instanceof QueryCacheEntry) {
            return null;
        }

        if ( ! $this->validator->isValid($key, $entry)) {
            $this->region->evict($key);

            return null;
        }

        $result      = array();
        $entityName  = reset($rsm->aliasMap); //@TODO find root entity
        $hasRelation = ( ! empty($rsm->relationMap));
        $persister   = $this->uow->getEntityPersister($entityName);
        $region      = $persister->getCacheRegionAcess()->getRegion();

        // @TODO - move to cache hydration componente
        foreach ($entry->result as $index => $entry) {

            if (($entityEntry = $region->get(new EntityCacheKey($entityName, $entry['identifier']))) === null) {
                return null;
            }

            if ( ! $hasRelation) {
                $result[$index]  = $this->uow->createEntity($entityEntry->class, $entityEntry->data, self::$hints);

                continue;
            }

            $data = $entityEntry->data;

            foreach ($entry['associations'] as $name => $assoc) {

                $assocPersister  = $this->uow->getEntityPersister($assoc['targetEntity']);
                $assocRegion     = $assocPersister->getCacheRegionAcess()->getRegion();

                if ($assoc['type'] & ClassMetadata::TO_ONE) {

                    if (($assocEntry = $assocRegion->get(new EntityCacheKey($assoc['targetEntity'], $assoc['identifier']))) === null) {
                        return null;
                    }

                    $data[$name] = $this->uow->createEntity($assocEntry->class, $assocEntry->data, self::$hints);

                    continue;
                }

                if ( ! isset($assoc['list']) || empty($assoc['list'])) {
                    continue;
                }

                $targetClass = $this->em->getClassMetadata($assoc['targetEntity']);
                $collection  = new PersistentCollection($this->em, $targetClass, new ArrayCollection());

                foreach ($assoc['list'] as $assocIndex => $assocId) {

                    if (($assocEntry = $assocRegion->get(new EntityCacheKey($assoc['targetEntity'], $assocId))) === null) {
                        return null;
                    }

                    $element = $this->uow->createEntity($assocEntry->class, $assocEntry->data, self::$hints);

                    $collection->hydrateSet($assocIndex, $element);
                }

                $data[$name] = $collection;

                $collection->setInitialized(true);
            }

            $result[$index] = $this->uow->createEntity($entityEntry->class, $data, self::$hints);;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @TODO - does not work recursively yet
     */
    public function put(QueryCacheKey $key, ResultSetMapping $rsm, array $result)
    {
        if ($rsm->scalarMappings) {
            throw new CacheException("Second level cache does not suport scalar results.");
        }

        if ( ! ($key->cacheMode & Cache::MODE_PUT)) {
            return false;
        }

        $data        = array();
        $entityName  = reset($rsm->aliasMap); //@TODO find root entity
        $hasRelation = ( ! empty($rsm->relationMap));
        $metadata    = $this->em->getClassMetadata($entityName);
        $persister   = $this->uow->getEntityPersister($entityName);

        if ( ! ($persister instanceof CachedPersister)) {
            throw CacheException::nonCacheableEntity($entityName);
        }

        $region = $persister->getCacheRegionAcess()->getRegion();

        foreach ($result as $index => $entity) {
            $identifier                     = $this->uow->getEntityIdentifier($entity);
            $data[$index]['identifier']     = $identifier;
            $data[$index]['associations']   = array();

            if (($key->cacheMode & Cache::MODE_REFRESH) || ! $region->contains($entityKey = new EntityCacheKey($entityName, $identifier))) {
                // Cancel put result if entity put fail
                if ( ! $persister->putEntityCache($entity, $entityKey)) {
                    return false;
                }
            }

            if ( ! $hasRelation) {
                continue;
            }

            // @TODO - move to cache hydration componente
            foreach ($rsm->relationMap as $name) {
                $assoc = $metadata->associationMappings[$name];

                if (($assocValue = $metadata->getFieldValue($entity, $name)) === null || $assocValue instanceof Proxy) {
                    continue;
                }

                if ( ! isset($assoc['cache'])) {
                    throw CacheException::nonCacheableEntityAssociation($entityName, $name);
                }

                $assocPersister  = $this->uow->getEntityPersister($assoc['targetEntity']);
                $assocRegion     = $assocPersister->getCacheRegionAcess()->getRegion();
                $assocMetadata   = $assocPersister->getClassMetadata();

                // Handle *-to-one associations
                if ($assoc['type'] & ClassMetadata::TO_ONE) {

                    $assocIdentifier = $this->uow->getEntityIdentifier($assocValue);

                    if (($key->cacheMode & Cache::MODE_REFRESH) || ! $assocRegion->contains($entityKey = new EntityCacheKey($assocMetadata->rootEntityName, $assocIdentifier))) {

                        // Cancel put result if association entity put fail
                        if ( ! $assocPersister->putEntityCache($assocValue, $entityKey)) {
                            return false;
                        }
                    }

                    $data[$index]['associations'][$name] = array(
                        'targetEntity'  => $assocMetadata->rootEntityName,
                        'identifier'    => $assocIdentifier,
                        'type'          => $assoc['type']
                    );

                    continue;
                }

                // Handle *-to-many associations
                $list = array();

                foreach ($assocValue as $assocItemIndex => $assocItem) {
                    $assocIdentifier = $this->uow->getEntityIdentifier($assocItem);

                    if (($key->cacheMode & Cache::MODE_REFRESH) || ! $assocRegion->contains($entityKey = new EntityCacheKey($assocMetadata->rootEntityName, $assocIdentifier))) {

                        // Cancel put result if entity put fail
                        if ( ! $assocPersister->putEntityCache($assocItem, $entityKey)) {
                            return false;
                        }
                    }

                    $list[$assocItemIndex] = $assocIdentifier;
                }

                $data[$index]['associations'][$name] = array(
                    'targetEntity'  => $assocMetadata->rootEntityName,
                    'type'          => $assoc['type'],
                    'list'          => $list,
                );
            }
        }

        return $this->region->put($key, new QueryCacheEntry($data));
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return $this->region->evictAll();
    }

    /**
     * {@inheritdoc}
     */
    public function getRegion()
    {
        return $this->region;
    }
}