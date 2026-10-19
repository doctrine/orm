<?php

declare(strict_types=1);

namespace Doctrine\Performance\Persisters;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\Performance\EntityManagerFactory;
use Doctrine\Tests\Models\CMS\CmsGroup;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use PhpBench\Benchmark\Metadata\Annotations\ParamProviders;

use function range;

/**
 * @BeforeMethods({"init"})
 * @ParamProviders({"provideSizes"})
 */
final class BasicEntityPersisterParametersBench
{
    private EntityManagerInterface $entityManager;
    private EntityPersister $persister;

    /** @var EntityRepository<CmsGroup> */
    private EntityRepository $repository;

    /** @var array{id: list<int>} */
    private array $criteria;

    /** @param array{size: int} $params */
    public function init(array $params): void
    {
        $this->entityManager = EntityManagerFactory::getEntityManager([CmsGroup::class]);
        $this->persister     = $this->entityManager->getUnitOfWork()->getEntityPersister(CmsGroup::class);
        $this->repository    = $this->entityManager->getRepository(CmsGroup::class);
        $this->criteria      = ['id' => range(1, $params['size'])];

        $connection = $this->entityManager->getConnection();
        $statement  = $connection->prepare('INSERT INTO cms_groups (id, name) VALUES (?, ?)');

        foreach ($this->criteria['id'] as $id) {
            $statement->bindValue(1, $id);
            $statement->bindValue(2, 'group-' . $id);
            $statement->executeStatement();
        }
    }

    public function benchExpandParameters(): void
    {
        $this->persister->expandParameters($this->criteria);
    }

    public function benchFindBy(): void
    {
        $this->entityManager->clear();
        $this->repository->findBy($this->criteria);
    }

    /** @return iterable<string, array{size: int}> */
    public function provideSizes(): iterable
    {
        yield '100 IDs' => ['size' => 100];
        yield '1000 IDs' => ['size' => 1000];
        yield '10000 IDs' => ['size' => 10000];
    }
}
