<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Exception\InstanceOfTheWrongTypeEncountered;
use Doctrine\Tests\Models\Company\CompanyFixContract;
use Doctrine\Tests\Models\Company\CompanyFlexContract;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('DDC-1041')]
class DDC1041Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('company');

        parent::setUp();
    }

    public function testGrabWrongSubtypeReturnsNullOrThrows(): void
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(2000);

        $this->_em->persist($fix);
        $this->_em->flush();

        $id = $fix->getId();

        self::assertNull($this->_em->find(CompanyFlexContract::class, $id));

        try {
            $this->_em->getReference(CompanyFlexContract::class, $id);
        } catch (InstanceOfTheWrongTypeEncountered) {
        }

        try {
            $this->_em->getPartialReference(CompanyFlexContract::class, $id);
        } catch (InstanceOfTheWrongTypeEncountered) {
        }
    }
}
