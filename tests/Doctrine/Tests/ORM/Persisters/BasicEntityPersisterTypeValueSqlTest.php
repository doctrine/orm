<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\Persisters\BasicEntityPersister;
use Doctrine\Tests\Models\CustomType\CustomTypeParent;
use Doctrine\Tests\Models\CustomType\CustomTypeChild;
use Doctrine\Tests\Models\CustomType\CustomTypeFriend;

require_once __DIR__ . '/../../TestInit.php';

class BasicEntityPersisterTypeValueSqlTest extends \Doctrine\Tests\OrmTestCase
{
    protected $_persister;
    protected $_em;

    protected function setUp()
    {
        parent::setUp();

        $this->_em = $this->_getTestEntityManager();

        $this->_persister = new BasicEntityPersister($this->_em, $this->_em->getClassMetadata("Doctrine\Tests\Models\CustomType\CustomTypeParent"));

        if (DBALType::hasType('negative_to_positive')) {
            DBALType::overrideType('negative_to_positive', '\Doctrine\Tests\DbalTypes\NegativeToPositiveType');
        } else {
            DBALType::addType('negative_to_positive', '\Doctrine\Tests\DbalTypes\NegativeToPositiveType');
        }
    }

    public function testGetInsertSQLUsesTypeValuesSQL()
    {
        $method = new \ReflectionMethod($this->_persister, '_getInsertSQL');
        $method->setAccessible(true);

        $sql = $method->invoke($this->_persister);

        $this->assertEquals('INSERT INTO customtype_parents (customInteger, child_id) VALUES (ABS(?), ABS(?))', $sql);
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

        $this->assertEquals('UPDATE customtype_parents SET customInteger = ABS(?), child_id = ABS(?) WHERE id = ABS(?)', $executeUpdates[0]['query']);
    }

    public function testDeleteUsesTypeValuesSQL()
    {
        $parent = new CustomTypeParent();

        $this->_em->getUnitOfWork()->registerManaged($parent, array('id' => 1), array());

        $this->_persister->delete($parent);

        $executeUpdates = $this->_em->getConnection()->getExecuteUpdates();

        $update = array_pop($executeUpdates);
        $this->assertEquals('DELETE FROM customtype_parents WHERE id = ABS(?)', $update['query']);

        $update = array_pop($executeUpdates);
        $this->assertEquals('DELETE FROM customtype_parent_friends WHERE friend_customtypeparent_id = ABS(?)', $update['query']);

        $update = array_pop($executeUpdates);
        $this->assertEquals('DELETE FROM customtype_parent_friends WHERE customtypeparent_id = ABS(?)', $update['query']);
    }

    public function testGetSelectConditionSQLUsesTypeValuesSQL()
    {
        $method = new \ReflectionMethod($this->_persister, '_getSelectConditionSQL');
        $method->setAccessible(true);

        $sql = $method->invoke($this->_persister,  array('customInteger' => 1, 'child' => 1));

        $this->assertEquals('t0.customInteger = ABS(?) AND t0.child_id = ABS(?)', $sql);
    }
}
