<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Quote\Group;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1845
 * @group DDC-1843
 */
class DDC1843Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    protected function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata('Doctrine\Tests\Models\Quote\User'),
                $this->_em->getClassMetadata('Doctrine\Tests\Models\Quote\Group'),
                $this->_em->getClassMetadata('Doctrine\Tests\Models\Quote\Phone'),
                $this->_em->getClassMetadata('Doctrine\Tests\Models\Quote\Address'),
            ));
        } catch(\Exception $e) {
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
        $e1     = $this->_em->find('Doctrine\Tests\Models\Quote\Group', $e1Id);
        $e2     = $this->_em->find('Doctrine\Tests\Models\Quote\Group', $e2Id);
        $e3     = $this->_em->find('Doctrine\Tests\Models\Quote\Group', $e3Id);
        $e4     = $this->_em->find('Doctrine\Tests\Models\Quote\Group', $e4Id);

        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\Group', $e1);
        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\Group', $e2);
        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\Group', $e3);
        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\Group', $e4);

        $this->assertEquals($e1Id, $e1->id);
        $this->assertEquals($e2Id, $e2->id);
        $this->assertEquals($e3Id, $e3->id);
        $this->assertEquals($e4Id, $e4->id);


        $this->assertEquals('Parent Bar 1', $e1->name);
        $this->assertEquals('Parent Foo 2', $e2->name);
        $this->assertEquals('Bar 3', $e3->name);
        $this->assertEquals('Foo 4', $e4->name);

        $e1->name = 'Parent Bar 11';
        $e2->name = 'Parent Foo 22';
        $e3->name = 'Bar 33';
        $e4->name = 'Foo 44';

        // Update
        $this->_em->persist($e1);
        $this->_em->persist($e2);
        $this->_em->persist($e3);
        $this->_em->persist($e4);
        $this->_em->flush();

        $this->assertEquals('Parent Bar 11', $e1->name);
        $this->assertEquals('Parent Foo 22', $e2->name);
        $this->assertEquals('Bar 33', $e3->name);
        $this->assertEquals('Foo 44', $e4->name);

        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\Group', $e1);
        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\Group', $e2);
        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\Group', $e3);
        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\Group', $e4);

        $this->assertEquals($e1Id, $e1->id);
        $this->assertEquals($e2Id, $e2->id);
        $this->assertEquals($e3Id, $e3->id);
        $this->assertEquals($e4Id, $e4->id);

        $this->assertEquals('Parent Bar 11', $e1->name);
        $this->assertEquals('Parent Foo 22', $e2->name);
        $this->assertEquals('Bar 33', $e3->name);
        $this->assertEquals('Foo 44', $e4->name);

        // Delete
        $this->_em->remove($e4);
        $this->_em->remove($e3);
        $this->_em->remove($e2);
        $this->_em->remove($e1);
        
        $this->_em->flush();
        $this->_em->clear();


        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\Group', $e1);
        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\Group', $e2);
        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\Group', $e3);
        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\Group', $e4);

        // Retreave
        $e1     = $this->_em->find('Doctrine\Tests\Models\Quote\Group', $e1Id);
        $e2     = $this->_em->find('Doctrine\Tests\Models\Quote\Group', $e2Id);
        $e3     = $this->_em->find('Doctrine\Tests\Models\Quote\Group', $e3Id);
        $e4     = $this->_em->find('Doctrine\Tests\Models\Quote\Group', $e4Id);

        $this->assertNull($e1);
        $this->assertNull($e2);
        $this->assertNull($e3);
        $this->assertNull($e4);
    }

}