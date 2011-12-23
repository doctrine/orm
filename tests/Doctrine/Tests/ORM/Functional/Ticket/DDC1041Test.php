<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1041
 */
class DDC1041Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('company');
        parent::setUp();
    }

    public function testGrabWrongSubtypeReturnsNull()
    {
        $fix = new \Doctrine\Tests\Models\Company\CompanyFixContract();
        $fix->setFixPrice(2000);

        $this->_em->persist($fix);
        $this->_em->flush();

        $id = $fix->getId();

        $this->assertNull($this->_em->find('Doctrine\Tests\Models\Company\CompanyFlexContract', $id));
        $this->assertNull($this->_em->getReference('Doctrine\Tests\Models\Company\CompanyFlexContract', $id));
        $this->assertNull($this->_em->getPartialReference('Doctrine\Tests\Models\Company\CompanyFlexContract', $id));
    }
}