<?php

namespace Doctrine\Tests\ORM\Persisters;

use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Tests\Models\GeoNames\Admin1AlternateName;
use Doctrine\Tests\OrmTestCase;

class BasicEntityPersisterCompositeTypeSqlTest extends OrmTestCase
{
    /**
     * @var BasicEntityPersister
     */
    protected $_persister;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $_em;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->_em = $this->_getTestEntityManager();
        $this->_persister = new BasicEntityPersister($this->_em, $this->_em->getClassMetadata(Admin1AlternateName::class));
    }

    public function testSelectConditionStatementEq()
    {
        $statement = $this->_persister->getSelectConditionStatementSQL('admin1', 1, [], Comparison::EQ);
        $this->assertEquals('t0.admin1 = ? AND t0.country = ?', $statement);
    }

    public function testSelectConditionStatementEqNull()
    {
        $statement = $this->_persister->getSelectConditionStatementSQL('admin1', null, [], Comparison::IS);
        $this->assertEquals('t0.admin1 IS NULL AND t0.country IS NULL', $statement);
    }

    public function testSelectConditionStatementNeqNull()
    {
        $statement = $this->_persister->getSelectConditionStatementSQL('admin1', null, [], Comparison::NEQ);
        $this->assertEquals('t0.admin1 IS NOT NULL AND t0.country IS NOT NULL', $statement);
    }

    /**
     * @expectedException Doctrine\ORM\ORMException
     */
    public function testSelectConditionStatementIn()
    {
        $this->_persister->getSelectConditionStatementSQL('admin1', [], [], Comparison::IN);
    }
}
