<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Quote\SimpleEntity;

/**
 * @group DDC-1719
 */
class DDC1719Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    const CLASS_NAME = 'Doctrine\Tests\ORM\Functional\Ticket\DDC1719SimpleEntity';

    protected function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(self::CLASS_NAME),
        ));
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->_schemaTool->dropSchema(array(
            $this->_em->getClassMetadata(self::CLASS_NAME),
        ));
    }

    public function testCreateRetrieveUpdateDelete()
    {
        $e1 = new DDC1719SimpleEntity('Bar 1');
        $e2 = new DDC1719SimpleEntity('Foo 1');

        // Create
        $this->_em->persist($e1);
        $this->_em->persist($e2);
        $this->_em->flush();
        $this->_em->clear();

        $e1Id   = $e1->id;
        $e2Id   = $e2->id;

        // Retrieve
        $e1     = $this->_em->find(self::CLASS_NAME, $e1Id);
        $e2     = $this->_em->find(self::CLASS_NAME, $e2Id);

        $this->assertInstanceOf(self::CLASS_NAME, $e1);
        $this->assertInstanceOf(self::CLASS_NAME, $e2);

        $this->assertEquals($e1Id, $e1->id);
        $this->assertEquals($e2Id, $e2->id);

        $this->assertEquals('Bar 1', $e1->value);
        $this->assertEquals('Foo 1', $e2->value);

        $e1->value = 'Bar 2';
        $e2->value = 'Foo 2';

        // Update
        $this->_em->persist($e1);
        $this->_em->persist($e2);
        $this->_em->flush();

        $this->assertEquals('Bar 2', $e1->value);
        $this->assertEquals('Foo 2', $e2->value);

        $this->assertInstanceOf(self::CLASS_NAME, $e1);
        $this->assertInstanceOf(self::CLASS_NAME, $e2);

        $this->assertEquals($e1Id, $e1->id);
        $this->assertEquals($e2Id, $e2->id);

        $this->assertEquals('Bar 2', $e1->value);
        $this->assertEquals('Foo 2', $e2->value);

        // Delete
        $this->_em->remove($e1);
        $this->_em->remove($e2);
        $this->_em->flush();


        $e1 = $this->_em->find(self::CLASS_NAME, $e1Id);
        $e2 = $this->_em->find(self::CLASS_NAME, $e2Id);

        $this->assertNull($e1);
        $this->assertNull($e2);
    }

}

/**
 * @Entity
 * @Table(name="`ddc-1719-simple-entity`")
 */
class DDC1719SimpleEntity
{

    /**
     * @Id
     * @Column(type="integer", name="`simple-entity-id`")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Column(type="string", name="`simple-entity-value`")
     */
    public $value;

    /**
     * @param string $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

}
