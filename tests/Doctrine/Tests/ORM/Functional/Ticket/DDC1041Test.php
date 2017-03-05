<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-1041
 */
class DDC1041Test extends OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('company');
        parent::setUp();
    }

    public function testGrabWrongSubtypeReturnsNull()
    {
        $fix = new Models\Company\CompanyFixContract();
        $fix->setFixPrice(2000);

        $this->_em->persist($fix);
        $this->_em->flush();

        $id = $fix->getId();

        $this->assertNull($this->_em->find(Models\Company\CompanyFlexContract::class, $id));
        $this->assertNull($this->_em->getReference(Models\Company\CompanyFlexContract::class, $id));
        $this->assertNull($this->_em->getPartialReference(Models\Company\CompanyFlexContract::class, $id));
    }
}
