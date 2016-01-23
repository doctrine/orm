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

namespace Shitty\ORM\Cache;

use Shitty\ORM\Mapping\ClassMetadata;
use Shitty\ORM\EntityManagerInterface;
use Shitty\ORM\Persisters\Collection\CollectionPersister;
use Shitty\ORM\Persisters\Entity\EntityPersister;

/**
 * Contract for building second level cache regions components.
 * 
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
interface CacheFactory
{
    /**
     * Build an entity persister for the given entity metadata.
     *
     * @param \Shitty\ORM\EntityManagerInterface            $em        The entity manager.
     * @param \Shitty\ORM\Persisters\Entity\EntityPersister $persister The entity persister that will be cached.
     * @param \Shitty\ORM\Mapping\ClassMetadata             $metadata  The entity metadata.
     *
     * @return \Shitty\ORM\Cache\Persister\Entity\CachedEntityPersister
     */
    public function buildCachedEntityPersister(EntityManagerInterface $em, EntityPersister $persister, ClassMetadata $metadata);

    /**
     * Build a collection persister for the given relation mapping.
     *
     * @param \Shitty\ORM\EntityManagerInterface                    $em        The entity manager.
     * @param \Shitty\ORM\Persisters\Collection\CollectionPersister $persister The collection persister that will be cached.
     * @param array                                                   $mapping   The association mapping.
     *
     * @return \Shitty\ORM\Cache\Persister\Collection\CachedCollectionPersister
     */
    public function buildCachedCollectionPersister(EntityManagerInterface $em, CollectionPersister $persister, array $mapping);

    /**
     * Build a query cache based on the given region name
     *
     * @param \Shitty\ORM\EntityManagerInterface $em         The Entity manager.
     * @param string                               $regionName The region name.
     *
     * @return \Shitty\ORM\Cache\QueryCache The built query cache.
     */
    public function buildQueryCache(EntityManagerInterface $em, $regionName = null);

    /**
     * Build an entity hydrator
     *
     * @param \Shitty\ORM\EntityManagerInterface $em       The Entity manager.
     * @param \Shitty\ORM\Mapping\ClassMetadata  $metadata The entity metadata.
     *
     * @return \Shitty\ORM\Cache\EntityHydrator The built entity hydrator.
     */
    public function buildEntityHydrator(EntityManagerInterface $em, ClassMetadata $metadata);

    /**
     * Build a collection hydrator
     *
     * @param \Shitty\ORM\EntityManagerInterface $em      The Entity manager.
     * @param array                                $mapping The association mapping.
     *
     * @return \Shitty\ORM\Cache\CollectionHydrator The built collection hydrator.
     */
    public function buildCollectionHydrator(EntityManagerInterface $em, array $mapping);

    /**
     * Build a cache region
     *
     * @param array $cache The cache configuration.
     *
     * @return \Shitty\ORM\Cache\Region The cache region.
     */
    public function getRegion(array $cache);

    /**
     * Build timestamp cache region
     *
     * @return \Shitty\ORM\Cache\TimestampRegion The timestamp region.
     */
    public function getTimestampRegion();

    /**
     * Build \Doctrine\ORM\Cache
     *
     * @param EntityManagerInterface $entityManager
     *
     * @return \Shitty\ORM\Cache
     */
    public function createCache(EntityManagerInterface $entityManager);
}
