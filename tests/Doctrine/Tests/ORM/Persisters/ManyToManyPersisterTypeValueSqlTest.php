<?php

namespace Doctrine\Tests\ORM\Persisters;

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\Persisters\ManyToManyPersister;
use Doctrine\Tests\Models\CustomType\CustomTypeParent;
use Doctrine\Tests\Models\CustomType\CustomTypeChild;
use Doctrine\Tests\Models\CustomType\CustomTypeFriend;
use Doctrine\Common\Collections\Expr\Comparison;

class ManyToManyPersisterTypeValueSqlTest extends \Doctrine\Tests\OrmTestCase
{
    /**
     * @var ManyToManyPersister
     */
    protected $persister;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        if (DBALType::hasType('negative_to_positive')) {
            DBALType::overrideType('negative_to_positive', '\Doctrine\Tests\DbalTypes\NegativeToPositiveType');
        } else {
            DBALType::addType('negative_to_positive', '\Doctrine\Tests\DbalTypes\NegativeToPositiveType');
        }

        if (DBALType::hasType('upper_case_string')) {
            DBALType::overrideType('upper_case_string', '\Doctrine\Tests\DbalTypes\UpperCaseStringType');
        } else {
            DBALType::addType('upper_case_string', '\Doctrine\Tests\DbalTypes\UpperCaseStringType');
        }

        $this->em        = $this->_getTestEntityManager();
        $this->persister = new ManyToManyPersister($this->em);
    }

    /**
     * @group DDC-2073
     */
    public function testSelectConditionStatementIsNull()
    {
        $statement = $this->persister->getSelectConditionStatementSQL('test', null, array(), Comparison::IS);
        $this->assertEquals('te.test IS NULL', $statement);
    }

    public function testSelectConditionStatementEqNull()
    {
        $statement = $this->persister->getSelectConditionStatementSQL('test', null, array(), Comparison::EQ);
        $this->assertEquals('te.test IS NULL', $statement);
    }

    public function testSelectConditionStatementNeqNull()
    {
        $statement = $this->persister->getSelectConditionStatementSQL('test', null, array(), Comparison::NEQ);
        $this->assertEquals('te.test IS NOT NULL', $statement);
    }

    /**
     * @group DDC-3056
     */
    public function testSelectConditionStatementWithMultipleValuesContainingNull()
    {
        $this->assertEquals(
            '(te.id IN (?) OR te.id IS NULL)',
            $this->persister->getSelectConditionStatementSQL('id', array(null))
        );

        $this->assertEquals(
            '(te.id IN (?) OR te.id IS NULL)',
            $this->persister->getSelectConditionStatementSQL('id', array(null, 123))
        );

        $this->assertEquals(
            '(te.id IN (?) OR te.id IS NULL)',
            $this->persister->getSelectConditionStatementSQL('id', array(123, null))
        );
    }
}
