<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Quote\Group;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1843
 */
class DDC1843Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    const CLASS_NAME = '\Doctrine\Tests\Models\Quote\Group';

    protected function setUp()
    {
        $this->markTestIncomplete();
        parent::setUp();

        $this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(self::CLASS_NAME),
            ));
        } catch(\Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    public function testCreateRetreaveUpdateDelete()
    {

        $e1 = new Group('Parent Bar 1');
        $e2 = new Group('Parent Foo 2');

        $this->_em->persist($e1);
        $this->_em->persist($e2);
        $this->_em->flush();

        $e3 = new Group('Bar 3', $e1);
        $e4 = new Group('Foo 4', $e2);

        // Create
        $this->_em->persist($e3);
        $this->_em->persist($e4);
        $this->_em->flush();
        $this->_em->clear();

        $e1Id   = $e1->id;
        $e2Id   = $e2->id;
        $e3Id   = $e3->id;
        $e4Id   = $e4->id;

        // Retreave
        $e1     = $this->_em->find(self::CLASS_NAME, $e1Id);
        $e2     = $this->_em->find(self::CLASS_NAME, $e2Id);
        $e3     = $this->_em->find(self::CLASS_NAME, $e3Id);
        $e4     = $this->_em->find(self::CLASS_NAME, $e4Id);

        $this->assertInstanceOf(self::CLASS_NAME, $e1);
        $this->assertInstanceOf(self::CLASS_NAME, $e2);
        $this->assertInstanceOf(self::CLASS_NAME, $e3);
        $this->assertInstanceOf(self::CLASS_NAME, $e4);

        $this->assertEquals($e1Id, $e1->id);
        $this->assertEquals($e2Id, $e2->id);
        $this->assertEquals($e3Id, $e3->id);
        $this->assertEquals($e4Id, $e4->id);

        return;

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