<?php

declare(strict_types=1);

namespace Doctrine\Performance\Query;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Performance\EntityManagerFactory;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use PhpBench\Benchmark\Metadata\Annotations\ParamProviders;

/**
 * @BeforeMethods({"init"})
 * @ParamProviders({"provideQueries"})
 */
final class ParameterLookupBench
{
    private AbstractQuery|QueryBuilder $query;

    private string $lastName;

    /** @param array{kind: string, size: int} $params */
    public function init(array $params): void
    {
        $em          = EntityManagerFactory::getEntityManager([]);
        $this->query = match ($params['kind']) {
            'DQL' => $em->createQuery(''),
            'native' => $em->createNativeQuery('', new ResultSetMapping()),
            'builder' => $em->createQueryBuilder(),
        };
        $parameters = [];
        for ($i = 0; $i < $params['size']; ++$i) {
            $parameters[] = new Parameter('p' . $i, $i);
        }

        $this->query->setParameters(new ArrayCollection($parameters));
        $this->lastName = 'p' . ($params['size'] - 1);
    }

    public function benchFirstParameter(): void
    {
        for ($i = 0; $i < 1000; ++$i) {
            $this->query->getParameter(':p0');
        }
    }

    public function benchLastParameter(): void
    {
        for ($i = 0; $i < 1000; ++$i) {
            $this->query->getParameter($this->lastName);
        }
    }

    public function benchMissingParameter(): void
    {
        for ($i = 0; $i < 1000; ++$i) {
            $this->query->getParameter('missing');
        }
    }

    /** @return iterable<string, array{kind: string, size: int}> */
    public function provideQueries(): iterable
    {
        foreach (['DQL', 'native', 'builder'] as $kind) {
            foreach ([10, 1000] as $size) {
                yield $kind . ' ' . $size => ['kind' => $kind, 'size' => $size];
            }
        }
    }
}
