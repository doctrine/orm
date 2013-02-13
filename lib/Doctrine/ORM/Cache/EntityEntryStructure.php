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

use Doctrine\ORM\Query;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Structured cache entry for entities
 *
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class EntityEntryStructure
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
     * @param \Doctrine\ORM\EntityManager $em The entity manager.
     */
    public function __construct(EntityManager $em)
    {
        $this->em   = $em;
        $this->uow  = $em->getUnitOfWork();
    }

    /**
     * @param \Doctrine\ORM\Mapping\ClassMetadata $metadata The entity metadata.
     * @param \Doctrine\ORM\Cache\EntityCacheKey  $key      The entity cache key.
     * @param object                              $entity   The entity.
     *
     * @return array
     */
    public function buildCacheEntry(ClassMetadata $metadata, EntityCacheKey $key, $entity)
    {
        $data = $this->uow->getOriginalEntityData($entity);
        $data = array_merge($data, $key->identifier); // why update has no identifier values ?

        foreach ($metadata->associationMappings as $name => $association) {

            if ( ! isset($association['cache'])) {
                unset($data[$name]);

                continue;
            }

            if ( ! isset($data[$name]) || $data[$name] === null) {
                continue;
            }

            if ($association['type'] & ClassMetadata::TO_ONE) {
                $data[$name] = $this->uow->getEntityIdentifier($data[$name]);
            }

            if ($association['type'] & ClassMetadata::TO_MANY) {
                unset($data[$name]); // handle collection here ?
            }
        }

        return $data;
    }

    /**
     * @param  \Doctrine\ORM\Mapping\ClassMetadata $metadata The entity metadata.
     * @param  \Doctrine\ORM\Cache\EntityCacheKey  $key      The entity cache key.
     * @param  array                               $cache    The entity data.
     * @param  object                              $entity   The entity to load the cache into. If not specified, a new entity is created.
     */
    public function loadCacheEntry(ClassMetadata $metadata, EntityCacheKey $key, array $cache, $entity = null)
    {
        if ($entity !== null) {
            $hints[Query::HINT_REFRESH]         = true;
            $hints[Query::HINT_REFRESH_ENTITY]  = $entity;
        }

        return $this->uow->createEntity($metadata->name, $cache, $hints);
    }
}
