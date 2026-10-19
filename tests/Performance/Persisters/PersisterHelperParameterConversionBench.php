<?php

declare(strict_types=1);

namespace Doctrine\Performance\Persisters;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Utility\PersisterHelper;
use Doctrine\Performance\EntityManagerFactory;
use Doctrine\Tests\Models\GeoNames\Admin1;
use Doctrine\Tests\Models\GeoNames\Country;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use PhpBench\Benchmark\Metadata\Annotations\ParamProviders;

use function array_chunk;
use function range;

/**
 * @BeforeMethods({"init"})
 * @ParamProviders({"provideSizes"})
 */
final class PersisterHelperParameterConversionBench
{
    private EntityManagerInterface $entityManager;

    /** @var list<int> */
    private array $values;

    /** @var list<list<int>> */
    private array $nestedValues;

    /** @var list<Admin1> */
    private array $entities;

    /** @param array{size: int} $params */
    public function init(array $params): void
    {
        $this->entityManager = EntityManagerFactory::getEntityManager([]);
        $this->values        = range(1, $params['size']);
        $this->nestedValues  = array_chunk($this->values, 100);
        $this->entities      = [];
        $country             = new Country('IT', 'Italy');

        foreach ($this->values as $id) {
            $this->entities[] = new Admin1($id, 'region-' . $id, $country);
        }

        $this->entityManager->getClassMetadata(Admin1::class);
        $this->entityManager->getClassMetadata(Country::class);
    }

    public function benchScalarArray(): void
    {
        PersisterHelper::convertToParameterValue($this->values, $this->entityManager);
    }

    public function benchNestedArrays(): void
    {
        PersisterHelper::convertToParameterValue($this->nestedValues, $this->entityManager);
    }

    public function benchCompositeEntityArray(): void
    {
        PersisterHelper::convertToParameterValue($this->entities, $this->entityManager);
    }

    public function benchIndividualCompositeEntities(): void
    {
        foreach ($this->entities as $entity) {
            PersisterHelper::convertToParameterValue($entity, $this->entityManager);
        }
    }

    /** @return iterable<string, array{size: int}> */
    public function provideSizes(): iterable
    {
        yield '100 values' => ['size' => 100];
        yield '1000 values' => ['size' => 1000];
        yield '10000 values' => ['size' => 10000];
    }
}
