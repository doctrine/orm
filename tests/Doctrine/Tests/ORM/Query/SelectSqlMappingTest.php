<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Query;

use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\ORM\Query as ORMQuery;
use Doctrine\Tests\Models\CMS\CmsProduct;
use Doctrine\Tests\OrmTestCase;
use Exception;
use function get_class;

class SelectSqlMappingTest extends OrmTestCase
{
    private $em;

    protected function setUp() : void
    {
        $this->em = $this->getTestEntityManager();
    }

    /**
     * Assert a valid SQL generation.
     *
     * @param string $dqlToBeTested
     * @param array $types
     */
    public function assertScalarTypes($dqlToBeTested, array $types)
    {
        try {
            $query = $this->em->createQuery($dqlToBeTested);

            $query
                ->setHint(ORMQuery::HINT_FORCE_PARTIAL_LOAD, true)
                ->useQueryCache(false);

            $r = new \ReflectionObject($query);
            $method = $r->getMethod('getResultSetMapping');
            $method->setAccessible(true);
            /** @var ORMQuery\ResultSetMapping $mapping */
            $mapping = $method->invoke($query);
            foreach($types as $key => $expectedType) {
                $alias = array_search($key, $mapping->scalarMappings);
                self::assertInstanceOf(
                    $expectedType,
                    $mapping->typeMappings[$alias],
                    "The field \"$key\" was expected as a $expectedType"
                );
            }
            $query->free();
        } catch (Exception $e) {
            $this->fail($e->getMessage() . "\n" . $e->getTraceAsString());
        }

    }

    /**
     * @group DDC-2235
     */
    public function testTypeFromMathematicNodeFunction() : void
    {
        $entity = CmsProduct::class;
        $this->assertScalarTypes("SELECT p, 
          count(p.id) as count, 
          SUM(p.price) as sales, 
          AVG(p.price) as average, 
          ABS(p.price) as absolute,
          LENGTH(p.name) as length
          FROM {$entity} p",
            [
                'count' => IntegerType::class,
                'sales' => FloatType::class,
                'average' => FloatType::class,
                'absolute' => FloatType::class,
                'length' => IntegerType::class
            ]
        );
    }
}

