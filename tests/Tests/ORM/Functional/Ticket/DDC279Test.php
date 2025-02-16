<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

class DDC279Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            DDC279EntityXAbstract::class,
            DDC279EntityX::class,
            DDC279EntityY::class,
            DDC279EntityZ::class,
        );
    }

    #[Group('DDC-279')]
    public function testDDC279(): void
    {
        $x = new DDC279EntityX();
        $y = new DDC279EntityY();
        $z = new DDC279EntityZ();

        $x->data = 'X';
        $y->data = 'Y';
        $z->data = 'Z';

        $x->y = $y;
        $y->z = $z;

        $this->_em->persist($x);
        $this->_em->persist($y);
        $this->_em->persist($z);

        $this->_em->flush();
        $this->_em->clear();

        $query = $this->_em->createQuery(
            'SELECT x, y, z FROM Doctrine\Tests\ORM\Functional\Ticket\DDC279EntityX x ' .
            'INNER JOIN x.y y INNER JOIN y.z z WHERE x.id = ?1',
        )->setParameter(1, $x->id);

        $result = $query->getResult();

        $expected1 = 'Y';
        $expected2 = 'Z';

        self::assertCount(1, $result);

        self::assertEquals($expected1, $result[0]->y->data);
        self::assertEquals($expected2, $result[0]->y->z->data);
    }
}


#[Entity]
#[InheritanceType('JOINED')]
#[DiscriminatorColumn(name: 'discr', type: 'string')]
#[DiscriminatorMap(['DDC279EntityX' => 'DDC279EntityX'])]
abstract class DDC279EntityXAbstract
{
    /** @var int */
    #[Id]
    #[GeneratedValue]
    #[Column(name: 'id', type: 'integer')]
    public $id;

    /** @var string */
    #[Column(type: 'string', length: 255)]
    public $data;
}

#[Entity]
class DDC279EntityX extends DDC279EntityXAbstract
{
    /** @var DDC279EntityY */
    #[OneToOne(targetEntity: 'DDC279EntityY')]
    #[JoinColumn(name: 'y_id', referencedColumnName: 'id')]
    public $y;
}

#[Entity]
class DDC279EntityY
{
    /** @var int */
    #[Id]
    #[GeneratedValue]
    #[Column(name: 'id', type: 'integer')]
    public $id;

    /** @var string */
    #[Column(type: 'string', length: 255)]
    public $data;

    /** @var DDC279EntityZ */
    #[OneToOne(targetEntity: 'DDC279EntityZ')]
    #[JoinColumn(name: 'z_id', referencedColumnName: 'id')]
    public $z;
}

#[Entity]
class DDC279EntityZ
{
    /** @var int */
    #[Id]
    #[GeneratedValue]
    #[Column(name: 'id', type: 'integer')]
    public $id;

    /** @var string */
    #[Column(type: 'string', length: 255)]
    public $data;
}
