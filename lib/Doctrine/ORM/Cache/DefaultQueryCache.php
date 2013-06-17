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

use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Cache\QueryCacheEntry;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\EntityManager;

/**
 * Default query cache implementation.
 *
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class DefaultQueryCache implements QueryCache
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
     * @var \Doctrine\ORM\Cache\Region
     */
    private $region;

    /**
     * @param \Doctrine\ORM\EntityManager   $em     The entity manager.
     * @param \Doctrine\ORM\Cache\Region    $region
     */
    public function __construct(EntityManager $em, Region $region)
    {
        $this->em       = $em;
        $this->region   = $region;
        $this->uow      = $em->getUnitOfWork();
    }

    /**
     * {@inheritdoc}
     */
    public function get(QueryCacheKey $key, ResultSetMapping $rsm)
    {
        $entry = $this->region->get($key);

        if ( ! $entry instanceof QueryCacheEntry) {
            return null;
        }

        $result     = array();
        $entityName = reset($rsm->aliasMap); //@TODO find root entity
        $persister  = $this->uow->getEntityPersister($entityName);
        $region     = $persister->getCacheRegionAcess()->getRegion();
        
        foreach ($entry->result as $index => $value) {

            if ( ! $region->contains(new EntityCacheKey($entityName, $value))) {
                return null;
            }

            $result[$index] = $this->em->getReference($entityName, $value);

            //@TODO - handle associations ?
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function put(QueryCacheKey $key, ResultSetMapping $rsm, array $result)
    {
        $data = array();

        foreach ($result as $index => $value) {
            $data[$index] = $this->uow->getEntityIdentifier($value);

            //@TODO - handle associations ?
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