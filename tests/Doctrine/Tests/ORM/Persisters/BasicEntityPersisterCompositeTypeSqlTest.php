<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Persisters;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\Tests\Models\GeoNames\Admin1AlternateName;
use Doctrine\Tests\OrmTestCase;

class BasicEntityPersisterCompositeTypeSqlTest extends OrmTestCase
{
    /** @var BasicEntityPersister */
    protected $_persister;

    /** @var EntityManager */
    protected $_em;

    protected function setUp(): void
    {
        parent::setUp();

        $this->_em        = $this->_getTestEntityManager();
        $this->_persister = new BasicEntityPersister($this->_em, $this->_em->getClassMetadata(Admin1AlternateName::class));
    }

    public function testSelectConditionStatementEq(): void
    {
        $statement = $this->_persister->getSelectConditionStatementSQL('admin1', 1, [], Comparison::EQ);
        $this->assertEquals('t0.admin1 = ? AND t0.country = ?', $statement);
    }

    public function testSelectConditionStatementEqNull(): void
    {
        $statement = $this->_persister->getSelectConditionStatementSQL('admin1', null, [], Comparison::IS);
        $this->assertEquals('t0.admin1 IS NULL AND t0.country IS NULL', $statement);
    }

    public function testSelectConditionStatementNeqNull(): void
    {
        $statement = $this->_persister->getSelectConditionStatementSQL('admin1', null, [], Comparison::NEQ);
        $this->assertEquals('t0.admin1 IS NOT NULL AND t0.country IS NOT NULL', $statement);
    }

    public function testSelectConditionStatementIn(): void
    {
        $this->expectException('Doctrine\ORM\ORMException');
        $this->_persister->getSelectConditionStatementSQL('admin1', [], [], Comparison::IN);
    }
}
