<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Generic\BooleanModel;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC949Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('generic');

        parent::setUp();
    }

    /** @group DDC-949 */
    public function testBooleanThroughRepository(): void
    {
        $true               = new BooleanModel();
        $true->booleanField = true;

        $false               = new BooleanModel();
        $false->booleanField = false;

        $this->_em->persist($true);
        $this->_em->persist($false);
        $this->_em->flush();
        $this->_em->clear();

        $true  = $this->_em->getRepository(BooleanModel::class)->findOneBy(['booleanField' => true]);
        $false = $this->_em->getRepository(BooleanModel::class)->findOneBy(['booleanField' => false]);

        self::assertInstanceOf(BooleanModel::class, $true, 'True model not found');
        self::assertTrue($true->booleanField, 'True Boolean Model should be true.');

        self::assertInstanceOf(BooleanModel::class, $false, 'False model not found');
        self::assertFalse($false->booleanField, 'False Boolean Model should be false.');
    }
}
