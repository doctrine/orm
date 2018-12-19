<?php

declare(strict_types=1);

namespace Doctrine\Performance\Query;

use DateTime;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\ORM\Query;
use Doctrine\Performance\EntityManagerFactory;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use function range;

/**
 * @BeforeMethods({"init"})
 */
final class QueryBoundParameterProcessingBench
{
    /** @var Query */
    private $parsedQueryWithInferredParameterType;

    /** @var Query */
    private $parsedQueryWithDeclaredParameterType;

    public function init() : void
    {
        $entityManager = EntityManagerFactory::makeEntityManagerWithNoResultsConnection();

        // Note: binding a lot of parameters because DQL operations are noisy due to hydrators and other components
        //       kicking in, so we make the parameter operations more noticeable.
        $dql = <<<'DQL'
SELECT e
FROM Doctrine\Tests\Models\Generic\DateTimeModel e
WHERE
    e.datetime = :parameter1
    OR
    e.datetime = :parameter2
    OR
    e.datetime = :parameter3
    OR
    e.datetime = :parameter4
    OR
    e.datetime = :parameter5
    OR
    e.datetime = :parameter6
    OR
    e.datetime = :parameter7
    OR
    e.datetime = :parameter8
    OR
    e.datetime = :parameter9
    OR
    e.datetime = :parameter10
DQL;

        $this->parsedQueryWithInferredParameterType = $entityManager->createQuery($dql);
        $this->parsedQueryWithDeclaredParameterType = $entityManager->createQuery($dql);

        foreach (range(1, 10) as $index) {
            $this->parsedQueryWithInferredParameterType->setParameter('parameter' . $index, new DateTime());
            $this->parsedQueryWithDeclaredParameterType->setParameter('parameter' . $index, new DateTime(), DateTimeType::DATETIME);
        }

        // Force parsing upfront - we don't benchmark that bit in this scenario
        $this->parsedQueryWithInferredParameterType->getSQL();
        $this->parsedQueryWithDeclaredParameterType->getSQL();
    }

    public function benchExecuteParsedQueryWithInferredParameterType() : void
    {
        $this->parsedQueryWithInferredParameterType->execute();
    }

    public function benchExecuteParsedQueryWithDeclaredParameterType() : void
    {
        $this->parsedQueryWithDeclaredParameterType->execute();
    }
}
