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

namespace Doctrine\ORM\Persisters;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * PersisterFactory
 *
 * @package Doctrine\ORM\Persisters
 */
class PersisterFactory
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var boolean
     */
    private $hasCache;

    /**
     * @var array
     */
    private $entityPersisters = array();

    /**
     * @var array
     */
    private $collectionPersisters = array();

    /**
     * Constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->hasCache      = $entityManager->getConfiguration()->isSecondLevelCacheEnabled();
    }

    /**
     * Clear the list of existing loaded persisters.
     *
     */
    public function clear()
    {
        $this->entityPersisters = $this->collectionPersisters = array();
    }

    /**
     * Retrieve the existing entity persisters.
     *
     * @return array
     */
    public function getEntityPersisters()
    {
        return $this->entityPersisters;
    }

    /**
     * Retrieve the existing collection persisters.
     *
     * @return array
     */
    public function getCollectionPersisters()
    {
        return $this->collectionPersisters;
    }

    /**
     * Get or Create the EntityPersister for a given Entity name.
     *
     * @param string $entityName The name of the Entity.
     *
     * @return \Doctrine\ORM\Persisters\Entity\EntityPersister
     */
    public function getOrCreateEntityPersister($entityName)
    {
        if (isset($this->entityPersisters[$entityName])) {
            return $this->entityPersisters[$entityName];
        }

        $class = $this->entityManager->getClassMetadata($entityName);

        switch (true) {
            case ($class->isInheritanceTypeNone()):
                $persister = new Entity\BasicEntityPersister($this->entityManager, $class);
                break;

            case ($class->isInheritanceTypeSingleTable()):
                $persister = new Entity\SingleTablePersister($this->entityManager, $class);
                break;

            case ($class->isInheritanceTypeJoined()):
                $persister = new Entity\JoinedSubclassPersister($this->entityManager, $class);
                break;

            default:
                throw new \RuntimeException('No persister found for entity.');
        }

        $hasCache = $this->entityManager->getConfiguration()->isSecondLevelCacheEnabled();

        if ($hasCache && $class->cache !== null) {
            $persister = $this->entityManager->getConfiguration()
                ->getSecondLevelCacheConfiguration()
                ->getCacheFactory()
                ->buildCachedEntityPersister($this->entityManager, $persister, $class);
        }

        $this->entityPersisters[$entityName] = $persister;

        return $this->entityPersisters[$entityName];
    }

    /**
     * Get or Create a collection persister for a collection-valued association.
     *
     * @param array $association
     *
     * @return \Doctrine\ORM\Persisters\Collection\CollectionPersister
     */
    public function getOrCreateCollectionPersister(array $association)
    {
        $role = isset($association['cache'])
            ? $association['sourceEntity'] . '::' . $association['fieldName']
            : $association['type'];

        if (isset($this->collectionPersisters[$role])) {
            return $this->collectionPersisters[$role];
        }

        $persister = ClassMetadata::ONE_TO_MANY === $association['type']
            ? new Collection\OneToManyPersister($this->entityManager)
            : new Collection\ManyToManyPersister($this->entityManager);

        $hasCache = $this->entityManager->getConfiguration()->isSecondLevelCacheEnabled();

        if ($hasCache && isset($association['cache'])) {
            $persister = $this->entityManager->getConfiguration()
                ->getSecondLevelCacheConfiguration()
                ->getCacheFactory()
                ->buildCachedCollectionPersister($this->entityManager, $persister, $association);
        }

        $this->collectionPersisters[$role] = $persister;

        return $this->collectionPersisters[$role];
    }
}
