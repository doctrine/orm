<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Company\CompanyFixContract;
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
        $fix = new CompanyFixContract();
        $fix->setFixPrice(2000);

        $this->_em->persist($fix);
        $this->_em->flush();

        $id = $fix->getId();

        self::assertNull($this->_em->find('Doctrine\Tests\Models\Company\CompanyFlexContract', $id));
        self::assertNull($this->_em->getReference('Doctrine\Tests\Models\Company\CompanyFlexContract', $id));
        self::assertNull($this->_em->getPartialReference('Doctrine\Tests\Models\Company\CompanyFlexContract', $id));
    }
}
