<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Quote\SimpleEntity;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1719
 */
class DDC1719Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    const CLASS_NAME = '\Doctrine\Tests\Models\Quote\SimpleEntity';

    protected function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(self::CLASS_NAME),
            ));
        } catch(\Exception $e) {
        }
    }

    public function testCreateRetrieveUpdateDelete()
    {
        $e1 = new SimpleEntity('Bar 1');
        $e2 = new SimpleEntity('Foo 1');

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