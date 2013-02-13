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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Cache\CollectionCacheKey;

/**
 * Structured cache entry for collection
 *
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class CollectionEntryStructure
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
     * @param \Doctrine\ORM\Mapping\ClassMetadata           $metadata   The entity metadata.
     * @param \Doctrine\ORM\Cache\CollectionCacheKey        $key        The cached collection key.
     * @param array|\Doctrine\Common\Collections\Collection $collection The collection.
     *
     * @return array
     */
    public function buildCacheEntry(ClassMetadata $metadata, CollectionCacheKey $key, $collection)
    {
        $data = array();

        foreach ($collection as $key => $entity) {
            $data[$key] = $this->uow->getEntityIdentifier($entity);
        }

        return $data;
    }

    /**
     * @param \Doctrine\ORM\Mapping\ClassMetadata    $metadata   The owning entity metadata.
     * @param \Doctrine\ORM\Cache\CollectionCacheKey $key        The cached collection key.
     * @param array                                  $cache      Cached collection data.
     * @param Doctrine\ORM\PersistentCollection      $collection The collection to load the cache into.
     *
     * @return array
     */
    public function loadCacheEntry(ClassMetadata $metadata, CollectionCacheKey $key, array $cache, PersistentCollection $collection)
    {
        $list = array();

        foreach ($cache as $key => $entry) {
            $entity     = $this->em->getReference($metadata->rootEntityName, $entry);
            $list[$key] = $entity;

            $collection->hydrateSet($key, $entity);
        }

        return $list;
    }
}
