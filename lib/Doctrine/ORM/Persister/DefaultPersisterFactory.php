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

namespace Doctrine\ORM\Persister;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * This factory is used to create default persisters for entities or collections at runtime.
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 *
 * @since 2.5
 */
class DefaultPersisterFactory implements PersisterFactory
{
    /**
     * The entity persister instances used to persist entity instances.
     *
     * @var array<\Doctrine\ORM\Persister\Entity\EntityPersister>
     */
    private $entityPersisters = array();

    /**
     * The collection persister instances used to persist collections.
     *
     * @var array<\Doctrine\ORM\Persister\Collection\CollectionPersister>
     */
    private $collectionPersisters = array();

    /**
     * {@inheritdoc}
     */
    public function createEntityPersister(EntityManagerInterface $entityManager, ClassMetadata $classMetadata)
    {
        $entityName = $classMetadata->name;

        if (isset($this->entityPersisters[$entityName])) {
            return $this->entityPersisters[$entityName];
        }

        switch (true) {
            case ($classMetadata->customPersisterClassName):
                $persisterClass = $classMetadata->customPersisterClassName;
                $persister      = new $persisterClass($entityManager, $classMetadata);
                break;

            case ($classMetadata->isInheritanceTypeNone()):
                $persister = new Entity\BasicEntityPersister($entityManager, $classMetadata);
                break;

            case ($classMetadata->isInheritanceTypeSingleTable()):
                $persister = new Entity\SingleTableEntityPersister($entityManager, $classMetadata);
                break;

            case ($classMetadata->isInheritanceTypeJoined()):
                $persister = new Entity\JoinedSubclassEntityPersister($entityManager, $classMetadata);
                break;

            default:
                $persister = new Entity\UnionSubclassEntityPersister($entityManager, $classMetadata);
        }

        $this->entityPersisters[$entityName] = $persister;

        return $persister;
    }

    /**
     * {@inheritdoc}
     */
    public function createCollectionPersister(EntityManagerInterface $entityManager, array $association)
    {
        $type = $association['persisterClass']
            ? sprintf('%s::%s', $association['sourceEntity'], $association['fieldName'])
            : $association['type'];

        if (isset($this->collectionPersisters[$type])) {
            return $this->collectionPersisters[$type];
        }

        switch (true) {
            case ($association['persisterClass']):
                $persisterClass = $association['persisterClass'];
                $persister      = new $persisterClass($entityManager);
                break;

            case ($type === ClassMetadata::ONE_TO_MANY):
                $persister = new Collection\OneToManyCollectionPersister($entityManager);
                break;

            case ($type === ClassMetadata::MANY_TO_MANY):
                $persister = new Collection\ManyToManyCollectionPersister($entityManager);
                break;
        }

        $this->collectionPersisters[$type] = $persister;

        return $persister;
    }
}