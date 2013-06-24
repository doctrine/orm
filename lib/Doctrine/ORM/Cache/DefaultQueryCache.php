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
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Cache\QueryCacheEntry;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\AbstractQuery;

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
     * @var \Doctrine\ORM\Cache\Logging\CacheLogger
     */
    private $logger;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface  $em     The entity manager.
     * @param \Doctrine\ORM\Cache\Region            $region The query region.
     */
    public function __construct(EntityManagerInterface $em, Region $region)
    {
        $this->em       = $em;
        $this->region   = $region;
        $this->uow      = $em->getUnitOfWork();
        $this->logger   = $em->getConfiguration()->getSecondLevelCacheLogger();
    }

    /**
     * {@inheritdoc}
     *
     * @TODO - does not work recursively yet
     */
    public function get(QueryCacheKey $key, AbstractQuery $query)
    {
        $entry      = $this->region->get($key);
        $lifetime   = $query->getResultCacheLifetime();

        if ( ! $entry instanceof QueryCacheEntry) {
            return null;
        }

        if ($lifetime > 0 && ($entry->time + $lifetime) < time()) {
            $this->region->evict($key);

            return null;
        }

        $result     = array();
        $rsm        = $query->getResultSetMapping();
        $entityName = reset($rsm->aliasMap); //@TODO find root entity
        $metadata   = $this->em->getClassMetadata($entityName);
        $persister  = $this->uow->getEntityPersister($entityName);
        $region     = $persister->getCacheRegionAcess()->getRegion();

        // @TODO - move to cache hydration componente
        foreach ($entry->result as $index => $entry) {

            if ( ! $region->contains(new EntityCacheKey($entityName, $entry['identifier']))) {
                return null;
            }

            $entity         = $this->em->getReference($entityName, $entry['identifier']);
            $result[$index] = $entity;

            foreach ($entry['associations'] as $name => $assoc) {

                $assocPersister  = $this->uow->getEntityPersister($assoc['rootEntityName']);
                $assocRegion     = $assocPersister->getCacheRegionAcess()->getRegion();

                if ($assoc['type'] & ClassMetadata::TO_ONE) {

                    if ( ! $assocRegion->contains(new EntityCacheKey($assoc['rootEntityName'], $assoc['identifier']))) {
                        return null;
                    }

                    $metadata->setFieldValue($entity, $name, $this->em->getReference($assoc['entityName'], $assoc['identifier']));

                    continue;
                }

                if ( ! isset($assoc['list']) || empty($assoc['list'])) {
                    continue;
                }

                $oid         = spl_object_hash($entity);
                $targetClass = $this->em->getClassMetadata($assoc['rootEntityName']);
                $relation    = $metadata->associationMappings[$name];
                $collection  = new PersistentCollection($this->em, $targetClass, new ArrayCollection());

                foreach ($assoc['list'] as $assocIndex => $assocItem) {

                    if ( ! $assocRegion->contains(new EntityCacheKey($assoc['rootEntityName'], $assocItem['identifier']))) {
                        return null;
                    }

                    $element = $this->em->getReference($assocItem['entityName'], $assocItem['identifier']);

                    $collection->hydrateSet($assocIndex, $element);
                }

                $collection->setInitialized(true);
                $collection->setOwner($entity, $relation);
                $metadata->setFieldValue($entity, $name, $collection);
                $this->uow->setOriginalEntityProperty($oid, $name, $collection);
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @TODO - does not work recursively yet
     */
    public function put(QueryCacheKey $key, AbstractQuery $query, array $result)
    {
        $data        = array();
        $rsm         = $query->getResultSetMapping();
        $entityName  = reset($rsm->aliasMap); //@TODO find root entity
        $hasRelation = ( ! empty($rsm->relationMap));
        $metadata    = $this->em->getClassMetadata($entityName);
        $persister   = $this->uow->getEntityPersister($entityName);
        $region      = $persister->getCacheRegionAcess()->getRegion();

        foreach ($result as $index => $entity) {
            $identifier                     = $this->uow->getEntityIdentifier($entity);
            $data[$index]['identifier']     = $identifier;
            $data[$index]['associations']   = array();

            if ($region->contains($entityKey = new EntityCacheKey($entityName, $identifier))) {
                continue;
            }

            // Cancel put result if entity put fail
            if ( ! $persister->putEntityCache($entity, $entityKey)) {
                return;
            }

            if ( ! $hasRelation) {
                continue;
            }

            // @TODO - move to cache hydration componente
            foreach ($rsm->relationMap as $name) {
                $assoc = $metadata->associationMappings[$name];

                if (($assocValue = $metadata->getFieldValue($entity, $name)) === null) {
                    continue;
                }

                $assocMetadata   = $this->em->getClassMetadata($assoc['targetEntity']);
                $assocPersister  = $this->uow->getEntityPersister($assocMetadata->rootEntityName);
                $assocRegion     = $assocPersister->getCacheRegionAcess()->getRegion();

                // Handle *-to-one associations
                if ($assoc['type'] & ClassMetadata::TO_ONE) {

                    $assocPersister  = $this->uow->getEntityPersister($assocMetadata->rootEntityName);
                    $assocRegion     = $assocPersister->getCacheRegionAcess()->getRegion();
                    $assocIdentifier = $this->uow->getEntityIdentifier($assocValue);

                    if ( ! $assocRegion->contains($entityKey = new EntityCacheKey($assocMetadata->rootEntityName, $assocIdentifier))) {

                        // Cancel put result if entity put fail
                        if ( ! $assocPersister->putEntityCache($assocValue, $entityKey)) {
                            return;
                        }
                    }

                    $data[$index]['associations'][$name] = array(
                        'rootEntityName'=> $assocMetadata->rootEntityName,
                        'entityName'    => $assocMetadata->name,
                        'identifier'    => $assocIdentifier,
                        'type'          => $assoc['type']
                    );

                    continue;
                }

                // Handle *-to-many associations
                if (is_array($assocValue) && ! $assocValue instanceof Collection) {
                    continue;
                }

                $list = array();

                foreach ($assocValue as $assocItemIndex => $assocItem) {
                    $assocIdentifier = $this->uow->getEntityIdentifier($assocItem);

                    if ( ! $assocRegion->contains($entityKey = new EntityCacheKey($assocMetadata->rootEntityName, $assocIdentifier))) {

                        // Cancel put result if entity put fail
                        if ( ! $assocPersister->putEntityCache($assocItem, $entityKey)) {
                            return;
                        }
                    }

                    $list[$assocItemIndex] = array(
                        'entityName'    => $assocMetadata->name,
                        'identifier'    => $assocIdentifier,
                    );
                }

                $data[$index]['associations'][$name] = array(
                    'rootEntityName'=> $assocMetadata->rootEntityName,
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