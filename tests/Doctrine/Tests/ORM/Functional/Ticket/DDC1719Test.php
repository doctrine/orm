<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

/**
 * @group DDC-1719
 */
class DDC1719Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema(
            [
            $this->_em->getClassMetadata(DDC1719SimpleEntity::class),
            ]
        );
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->_schemaTool->dropSchema(
            [
            $this->_em->getClassMetadata(DDC1719SimpleEntity::class),
            ]
        );
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
        $e1     = $this->_em->find(DDC1719SimpleEntity::class, $e1Id);
        $e2     = $this->_em->find(DDC1719SimpleEntity::class, $e2Id);

        $this->assertInstanceOf(DDC1719SimpleEntity::class, $e1);
        $this->assertInstanceOf(DDC1719SimpleEntity::class, $e2);

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

        $this->assertInstanceOf(DDC1719SimpleEntity::class, $e1);
        $this->assertInstanceOf(DDC1719SimpleEntity::class, $e2);

        $this->assertEquals($e1Id, $e1->id);
        $this->assertEquals($e2Id, $e2->id);

        $this->assertEquals('Bar 2', $e1->value);
        $this->assertEquals('Foo 2', $e2->value);

        // Delete
        $this->_em->remove($e1);
        $this->_em->remove($e2);
        $this->_em->flush();


        $e1 = $this->_em->find(DDC1719SimpleEntity::class, $e1Id);
        $e2 = $this->_em->find(DDC1719SimpleEntity::class, $e2Id);

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
