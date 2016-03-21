<?php

namespace Doctrine\Tests\ORM\Persisters;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\Tests\Models\ReadOnlyColumn\Item;

class BasicEntityPersisterReadOnlyColumnTest extends \Doctrine\Tests\OrmTestCase
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

        $this->_persister = new BasicEntityPersister($this->_em, $this->_em->getClassMetadata('Doctrine\Tests\Models\ReadOnlyColumn\Item'));
    }

    public function testGetSelectSQLUsesReadOnlyColumn()
    {
        $method = new \ReflectionMethod($this->_persister, 'getSelectSQL');
        $method->setAccessible(true);

        $sql = $method->invoke($this->_persister, new Criteria());

        $this->assertGreaterThan(0, stripos($sql, '.generatedString'));
    }

    public function testGetInsertSQLUsesReadOnlyColumn()
    {
        $method = new \ReflectionMethod($this->_persister, 'getInsertSQL');
        $method->setAccessible(true);

        $sql = $method->invoke($this->_persister);

        $this->assertEquals('INSERT INTO readonly_column (label, content) VALUES (?, ?)', $sql);
    }

    public function testGetUpdateSQLUsesReadOnlyColumn()
    {
        $item = new Item();
        $this->_em->persist($item);
        $this->_em->flush();

        $item->label = 'foo';
        $item->generatedString = 'bar';

        $this->_em->getUnitOfWork()->computeChangeSets();

        $method = new \ReflectionMethod($this->_persister, 'update');
        $method->setAccessible(true);
        $method->invoke($this->_persister, $item);

        $updates = $this->_em->getConnection()->getExecuteUpdates();
        $lastUpdate = end($updates);

        $this->assertNotEmpty($lastUpdate);

        $sql = $lastUpdate['query'];
        $updateParams = $lastUpdate['params'];

        $this->assertContains('label', $sql, '', true);
        $this->assertContains('foo', $updateParams, '', true, true, true);

        $this->assertNotContains('generatedString', $sql, '', true);
        $this->assertNotContains('bar', $updateParams, '', true, true, true);
    }
}
