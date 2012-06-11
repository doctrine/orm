<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Quote\User;
use Doctrine\Tests\Models\Quote\Address;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1845
 * @group DDC-142
 */
class DDC142Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    protected function setUp()
    {
        parent::setUp();

        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata('Doctrine\Tests\Models\Quote\User'),
                $this->_em->getClassMetadata('Doctrine\Tests\Models\Quote\Group'),
                $this->_em->getClassMetadata('Doctrine\Tests\Models\Quote\Phone'),
                $this->_em->getClassMetadata('Doctrine\Tests\Models\Quote\Address'),
            ));
        } catch(\Exception $e) {
            //$this->fail($e->getMessage());
        }

    }

    public function testCreateRetreaveUpdateDelete()
    {

        $user           = new User;
        $user->name     = 'FabioBatSilva';
        $this->_em->persist($user);

        $address        = new Address;
        $address->zip   = '12345';
        $this->_em->persist($address);

        $this->_em->flush();

        $addressRef = $this->_em->getReference('Doctrine\Tests\Models\Quote\Address', $address->getId());

        $user->setAddress($addressRef);

        $this->_em->flush();
        $this->_em->clear();

        $this->assertNotNull($user->id);
        $this->markTestIncomplete();

        $user   = $this->_em->find('Doctrine\Tests\Models\Quote\User', $user->id);

    }

}