<?php

declare(strict_types=1);

namespace Doctrine\Performance\Persisters;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\Performance\EntityManagerFactory;
use Doctrine\Tests\Models\CMS\CmsGroup;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use PhpBench\Benchmark\Metadata\Annotations\ParamProviders;

use function defined;
use function range;

/**
 * @BeforeMethods({"init"})
 * @ParamProviders({"provideSizes"})
 */
final class BasicEntityPersisterCriteriaBench
{
    private EntityManagerInterface $entityManager;
    private EntityPersister $persister;

    /** @var EntityRepository<CmsGroup> */
    private EntityRepository $repository;

    private Criteria $inCriteria;
    private Criteria $notInCriteria;

    /** @param array{size: int} $params */
    public function init(array $params): void
    {
        $this->entityManager = EntityManagerFactory::getEntityManager([CmsGroup::class]);
        $this->persister     = $this->entityManager->getUnitOfWork()->getEntityPersister(CmsGroup::class);
        $this->repository    = $this->entityManager->getRepository(CmsGroup::class);

        $statement = $this->entityManager->getConnection()->prepare('INSERT INTO cms_groups (id, name) VALUES (?, ?)');

        foreach (range(1, $params['size']) as $id) {
            $statement->bindValue(1, $id);
            $statement->bindValue(2, 'group-' . $id);
            $statement->executeStatement();
        }

        $this->inCriteria = defined(Criteria::class . '::ASC') ? Criteria::create(true) : Criteria::create();
        $this->inCriteria->where(Criteria::expr()->in('id', range(1, $params['size'])));
        $this->notInCriteria = defined(Criteria::class . '::ASC') ? Criteria::create(true) : Criteria::create();
        $this->notInCriteria->where(Criteria::expr()->notIn('id', range($params['size'] + 1, $params['size'] * 2)));
    }

    public function benchExpandCriteriaParameters(): void
    {
        $this->persister->expandCriteriaParameters($this->inCriteria);
    }

    public function benchMatchingIn(): void
    {
        $this->entityManager->clear();
        $this->repository->matching($this->inCriteria)->toArray();
    }

    public function benchMatchingNotIn(): void
    {
        $this->entityManager->clear();
        $this->repository->matching($this->notInCriteria)->toArray();
    }

    /** @return iterable<string, array{size: int}> */
    public function provideSizes(): iterable
    {
        yield '100 IDs' => ['size' => 100];
        yield '1000 IDs' => ['size' => 1000];
        yield '10000 IDs' => ['size' => 10000];
    }
}
