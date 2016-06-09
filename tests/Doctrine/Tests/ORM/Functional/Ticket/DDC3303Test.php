<?php
namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\DDC3303\DDC3303Address;
use Doctrine\Tests\Models\DDC3303\DDC3303Employee;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC3303Test extends OrmFunctionalTestCase
{
    /**
     * @before
     */
    public function createSchema()
    {
        $this->_schemaTool->createSchema(array($this->_em->getClassMetadata(DDC3303Employee::class)));
    }

    public function testEmbeddedObjectsAreAlsoInherited()
    {
        $employee = new DDC3303Employee(
            'John Doe',
            new DDC3303Address('Somewhere', 123, 'Over the rainbow'),
            'Doctrine Inc'
        );

        $this->_em->persist($employee);
        $this->_em->flush();
        $this->_em->clear();

        $this->assertEquals($employee, $this->_em->find(DDC3303Employee::class, 1));
    }
}
