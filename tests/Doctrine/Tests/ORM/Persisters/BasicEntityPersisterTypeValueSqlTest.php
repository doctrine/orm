<?php

namespace Doctrine\Tests\ORM\Persisters;

use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\Persisters\BasicEntityPersister;
use Doctrine\Tests\Models\CustomType\CustomTypeParent;
use Doctrine\Tests\Models\CustomType\CustomTypeChild;
use Doctrine\Tests\Models\CustomType\CustomTypeFriend;
use Doctrine\Common\Collections\Expr\Comparison;

require_once __DIR__ . '/../../TestInit.php';

class BasicEntityPersisterTypeValueSqlTest extends \Doctrine\Tests\OrmTestCase
{
    protected $_persister;
    protected $_em;

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

        $this->_em = $this->_getTestEntityManager();

        $this->_persister = new BasicEntityPersister($this->_em, $this->_em->getClassMetadata("Doctrine\Tests\Models\CustomType\CustomTypeParent"));
    }

    public function testGetInsertSQLUsesTypeValuesSQL()
    {
        $method = new \ReflectionMethod($this->_persister, 'getInsertSQL');
        $method->setAccessible(true);

        $sql = $method->invoke($this->_persister);

        $this->assertEquals('INSERT INTO customtype_parents (customInteger, child_id) VALUES (ABS(?), ?)', $sql);
    }

    public function testUpdateUsesTypeValuesSQL()
    {
        $child = new CustomTypeChild();

        $parent = new CustomTypeParent();
        $parent->customInteger = 1;
        $parent->child = $child;

        $this->_em->getUnitOfWork()->registerManaged($parent, array('id' => 1), array('customInteger' => 0, 'child' => null));
        $this->_em->getUnitOfWork()->registerManaged($child, array('id' => 1), array());

        $this->_em->getUnitOfWork()->propertyChanged($parent, 'customInteger', 0, 1);
        $this->_em->getUnitOfWork()->propertyChanged($parent, 'child', null, $child);

        $this->_persister->update($parent);

        $executeUpdates = $this->_em->getConnection()->getExecuteUpdates();

        $this->assertEquals('UPDATE customtype_parents SET customInteger = ABS(?), child_id = ? WHERE id = ?', $executeUpdates[0]['query']);
    }

    public function testGetSelectConditionSQLUsesTypeValuesSQL()
    {
        $method = new \ReflectionMethod($this->_persister, 'getSelectConditionSQL');
        $method->setAccessible(true);

        $sql = $method->invoke($this->_persister,  array('customInteger' => 1, 'child' => 1));

        $this->assertEquals('t0.customInteger = ABS(?) AND t0.child_id = ?', $sql);
    }

    /**
     * @group DDC-1719
     */
    public function testStripNonAlphanumericCharactersFromSelectColumnListSQL()
    {
        $persister  = new BasicEntityPersister($this->_em, $this->_em->getClassMetadata('Doctrine\Tests\Models\Quote\SimpleEntity'));
        $method     = new \ReflectionMethod($persister, 'getSelectColumnsSQL');
        $method->setAccessible(true);

        $this->assertEquals('t0."simple-entity-id" AS simpleentityid1, t0."simple-entity-value" AS simpleentityvalue2', $method->invoke($persister));
    }

    /**
     * @group DDC-2073
     */
    public function testSelectConditionStatementIsNull()
    {
        $statement = $this->_persister->getSelectConditionStatementSQL('test', null, array(), Comparison::IS);
        $this->assertEquals('test IS NULL', $statement);
    }

    public function testSelectConditionStatementEqNull()
    {
        $statement = $this->_persister->getSelectConditionStatementSQL('test', null, array(), Comparison::EQ);
        $this->assertEquals('test IS NULL', $statement);
    }

    public function testSelectConditionStatementNeqNull()
    {
        $statement = $this->_persister->getSelectConditionStatementSQL('test', null, array(), Comparison::NEQ);
        $this->assertEquals('test IS NOT NULL', $statement);
    }
}
