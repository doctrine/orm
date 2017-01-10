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

        $this->em->persist($true);
        $this->em->persist($false);
        $this->em->flush();
        $this->em->clear();

        $true = $this->em->getRepository(BooleanModel::class)->findOneBy(['booleanField' => true]);
        $false = $this->em->getRepository(BooleanModel::class)->findOneBy(['booleanField' => false]);

        self::assertInstanceOf(BooleanModel::class, $true, "True model not found");
        self::assertTrue($true->booleanField, "True Boolean Model should be true.");

        self::assertInstanceOf(BooleanModel::class, $false, "False model not found");
        self::assertFalse($false->booleanField, "False Boolean Model should be false.");
    }
}
