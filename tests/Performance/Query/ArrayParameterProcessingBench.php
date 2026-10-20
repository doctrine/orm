<?php

declare(strict_types=1);

namespace Doctrine\Performance\Query;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Performance\EntityManagerFactory;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\Enums\UserStatus;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use PhpBench\Benchmark\Metadata\Annotations\ParamProviders;

/**
 * @BeforeMethods({"init"})
 * @ParamProviders({"provideQueries"})
 */
final class ArrayParameterProcessingBench
{
    private AbstractQuery $query;

    /** @var list<int> */
    private array $integers = [];

    /** @var array<string, int> */
    private array $sparseScalars = [];

    /** @var list<int|UserStatus> */
    private array $mixedEnums = [];

    /** @var list<int|CmsGroup> */
    private array $mixedEntities = [];

    /** @var list<list<int>> */
    private array $nestedArrays = [];

    /** @param array{kind: string, size: int} $params */
    public function init(array $params): void
    {
        $em          = EntityManagerFactory::getEntityManager([]);
        $this->query = match ($params['kind']) {
            'DQL' => $em->createQuery(''),
            'native' => $em->createNativeQuery('', new ResultSetMapping()),
            'subclass' => new class ($em) extends Query {
                public function processParameterValue(mixed $value): mixed
                {
                    return parent::processParameterValue($value);
                }
            },
        };
        $em->getClassMetadata(CmsGroup::class);

        $this->integers      = [];
        $this->sparseScalars = [];
        $this->mixedEnums    = [];
        $this->mixedEntities = [];
        $this->nestedArrays  = [];

        for ($i = 0; $i < $params['size']; ++$i) {
            $this->integers[]                      = $i;
            $this->sparseScalars['key' . ($i * 3)] = $i;
            $this->mixedEnums[]                    = $i % 10 === 0 ? UserStatus::Active : $i;
            $this->nestedArrays[]                  = [$i, $i + 1];
            if ($i % 10 === 0) {
                $group                 = new CmsGroup();
                $group->id             = $i + 1;
                $this->mixedEntities[] = $group;
            } else {
                $this->mixedEntities[] = $i;
            }
        }
    }

    /** @return mixed[] */
    public function benchIntegers(): array
    {
        return $this->query->processParameterValue($this->integers);
    }

    /** @return mixed[] */
    public function benchSparseScalars(): array
    {
        return $this->query->processParameterValue($this->sparseScalars);
    }

    /** @return mixed[] */
    public function benchMixedEnums(): array
    {
        return $this->query->processParameterValue($this->mixedEnums);
    }

    /** @return mixed[] */
    public function benchMixedEntities(): array
    {
        return $this->query->processParameterValue($this->mixedEntities);
    }

    /** @return mixed[] */
    public function benchNestedArrays(): array
    {
        return $this->query->processParameterValue($this->nestedArrays);
    }

    /** @return iterable<string, array{kind: string, size: int}> */
    public function provideQueries(): iterable
    {
        foreach (['DQL', 'native', 'subclass'] as $kind) {
            foreach ([10000, 100000] as $size) {
                yield $kind . ' ' . $size => ['kind' => $kind, 'size' => $size];
            }
        }
    }
}
