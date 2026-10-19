<?php

declare(strict_types=1);

namespace Doctrine\Performance\Persisters;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Persisters\Collection\ManyToManyPersister;
use Doctrine\Performance\EntityManagerFactory;
use Doctrine\Tests\Models\ManyToManyPersister\ChildClass;
use Doctrine\Tests\Models\ManyToManyPersister\OtherParentClass;
use Doctrine\Tests\Models\ManyToManyPersister\ParentClass;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use PhpBench\Benchmark\Metadata\Annotations\ParamProviders;

use function defined;
use function range;

/**
 * @BeforeMethods({"init"})
 * @ParamProviders({"provideSizes"})
 */
final class ManyToManyCriteriaBench
{
    private EntityManagerInterface $entityManager;
    private ManyToManyPersister $persister;
    private PersistentCollection $collection;
    private ChildClass $owner;
    private Criteria $inCriteria;
    private Criteria $notInCriteria;

    /** @param array{size: int} $params */
    public function init(array $params): void
    {
        $this->entityManager = EntityManagerFactory::getEntityManager([
            ParentClass::class,
            ChildClass::class,
            OtherParentClass::class,
        ]);
        $this->owner         = new ChildClass(7, new OtherParentClass(42));
        $this->persister     = new ManyToManyPersister($this->entityManager);

        $mapping          = $this->entityManager->getClassMetadata(ChildClass::class)->associationMappings['parents'];
        $this->collection = new PersistentCollection(
            $this->entityManager,
            $this->entityManager->getClassMetadata(ParentClass::class),
            new ArrayCollection(),
        );
        $this->collection->setOwner($this->owner, $mapping);

        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('INSERT INTO manytomanypersister_other_parent (id) VALUES (42)');
        $connection->executeStatement('INSERT INTO manytomanypersister_child (id1, other_parent_id) VALUES (7, 42)');
        $parentStatement = $connection->prepare('INSERT INTO manytomanypersister_parent (id) VALUES (?)');
        $joinStatement   = $connection->prepare('INSERT INTO parent_child (child_id1, child_id2, parent_id) VALUES (7, 42, ?)');

        foreach (range(1, $params['size']) as $id) {
            $parentStatement->bindValue(1, $id);
            $parentStatement->executeStatement();
            $joinStatement->bindValue(1, $id);
            $joinStatement->executeStatement();
        }

        $this->inCriteria = defined(Criteria::class . '::ASC') ? Criteria::create(true) : Criteria::create();
        $this->inCriteria->where(Criteria::expr()->in('id', range(1, $params['size'])));
        $this->notInCriteria = defined(Criteria::class . '::ASC') ? Criteria::create(true) : Criteria::create();
        $this->notInCriteria->where(Criteria::expr()->notIn('id', range($params['size'] + 1, $params['size'] * 2)));
    }

    public function benchLoadCriteriaIn(): void
    {
        $this->registerOwner();
        $this->persister->loadCriteria($this->collection, $this->inCriteria);
    }

    public function benchLoadCriteriaNotIn(): void
    {
        $this->registerOwner();
        $this->persister->loadCriteria($this->collection, $this->notInCriteria);
    }

    private function registerOwner(): void
    {
        $this->entityManager->clear();
        $this->entityManager->getUnitOfWork()->registerManaged($this->owner, ['id1' => 7, 'otherParent' => 42], []);
    }

    /** @return iterable<string, array{size: int}> */
    public function provideSizes(): iterable
    {
        yield '100 IDs' => ['size' => 100];
        yield '1000 IDs' => ['size' => 1000];
        yield '10000 IDs' => ['size' => 10000];
    }
}
