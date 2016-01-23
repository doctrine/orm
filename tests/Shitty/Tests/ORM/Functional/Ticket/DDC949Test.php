<?php

namespace Shitty\Tests\ORM\Functional\Ticket;

use Shitty\Common\Collections\ArrayCollection;
use Shitty\Tests\Models\Generic\BooleanModel;

class DDC949Test extends \Shitty\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('generic');
        parent::setUp();
    }

    /**
     * @group DDC-949
     */
    public function testBooleanThroughRepository()
    {
        $true = new BooleanModel();
        $true->booleanField = true;

        $false = new BooleanModel();
        $false->booleanField = false;

        $this->_em->persist($true);
        $this->_em->persist($false);
        $this->_em->flush();
        $this->_em->clear();

        $true = $this->_em->getRepository('Doctrine\Tests\Models\Generic\BooleanModel')->findOneBy(array('booleanField' => true));
        $false = $this->_em->getRepository('Doctrine\Tests\Models\Generic\BooleanModel')->findOneBy(array('booleanField' => false));

        $this->assertInstanceOf('Doctrine\Tests\Models\Generic\BooleanModel', $true, "True model not found");
        $this->assertTrue($true->booleanField, "True Boolean Model should be true.");

        $this->assertInstanceOf('Doctrine\Tests\Models\Generic\BooleanModel', $false, "False model not found");
        $this->assertFalse($false->booleanField, "False Boolean Model should be false.");
    }
}
