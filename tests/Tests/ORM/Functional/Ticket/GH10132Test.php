<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Enums\Suit;
use Doctrine\Tests\Models\GH10132\Complex;
use Doctrine\Tests\Models\GH10132\ComplexChild;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH10132Test extends OrmFunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            Complex::class,
            ComplexChild::class,
        );
    }

    public function testQueryBackedEnumInCompositeKeyJoin(): void
    {
        $complex = new Complex();
        $complex->setType(Suit::Clubs);

        $complexChild = new ComplexChild();
        $complexChild->setComplex($complex);

        $this->_em->persist($complex);
        $this->_em->persist($complexChild);
        $this->_em->flush();
        $this->_em->clear();

        $qb = $this->_em->createQueryBuilder();
        $qb->select('s')
            ->from(ComplexChild::class, 's')
            ->where('s.complexType = :complexType');

        $qb->setParameter('complexType', Suit::Clubs);

        self::assertNotNull($qb->getQuery()->getOneOrNullResult());
    }
}
