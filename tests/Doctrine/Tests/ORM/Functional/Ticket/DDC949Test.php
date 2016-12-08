<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Generic\BooleanModel;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC949Test extends OrmFunctionalTestCase
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

        $true = $this->_em->getRepository(BooleanModel::class)->findOneBy(['booleanField' => true]);
        $false = $this->_em->getRepository(BooleanModel::class)->findOneBy(['booleanField' => false]);

        $this->assertInstanceOf(BooleanModel::class, $true, "True model not found");
        $this->assertTrue($true->booleanField, "True Boolean Model should be true.");

        $this->assertInstanceOf(BooleanModel::class, $false, "False model not found");
        $this->assertFalse($false->booleanField, "False Boolean Model should be false.");
    }
}
