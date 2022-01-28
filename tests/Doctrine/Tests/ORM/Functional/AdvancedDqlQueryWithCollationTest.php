<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\Tests\Models\ProductsRomanCollation\ProductColor;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Functional Query tests.
 */
class AdvancedDqlQueryWithCollationTest extends OrmFunctionalTestCase
{
    /**
     * {@inheritdoc}
     *
     * @see \Doctrine\Tests\OrmFunctionalTestCase::setUp()
     */
    protected function setUp(): void
    {
        $this->useModelSet('products_roman');
        parent::setUp();
        if (! static::$sharedConn->getDatabasePlatform() instanceof MySQLPlatform) {
            self::markTestSkipped('The ' . self::class . ' requires the use of mysql.');
        }

        $this->generateFixture();
    }

    public function testDeleteAs(): void
    {
        $dql = 'DELETE Doctrine\Tests\Models\ProductsRomanCollation\ProductColor AS p';
        $this->_em->createQuery($dql)->getResult();

        $dql    = 'SELECT count(p) FROM Doctrine\Tests\Models\ProductsRomanCollation\ProductColor p';
        $result = $this->_em->createQuery($dql)->getSingleScalarResult();

        self::assertEquals(0, $result);
    }

    private function generateFixture(): void
    {
        $this->_em->persist(new ProductColor('P01', 'Red', 1));
        $this->_em->persist(new ProductColor('P02', 'Green', 12));
        $this->_em->persist(new ProductColor('P03', 'Blue', 123));
        $this->_em->flush();
        $this->_em->clear();
    }
}
