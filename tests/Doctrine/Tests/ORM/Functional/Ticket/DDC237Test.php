<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC237Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            DDC237EntityX::class,
            DDC237EntityY::class,
            DDC237EntityZ::class,
        );
    }

    public function testUninitializedProxyIsInitializedOnFetchJoin(): void
    {
        $x = new DDC237EntityX();
        $y = new DDC237EntityY();
        $z = new DDC237EntityZ();

        $x->data = 'X';
        $y->data = 'Y';
        $z->data = 'Z';

        $x->y = $y;
        $z->y = $y;

        $this->_em->persist($x);
        $this->_em->persist($y);
        $this->_em->persist($z);

        $this->_em->flush();
        $this->_em->clear();

        $x2 = $this->_em->find($x::class, $x->id); // proxy injected for Y
        self::assertTrue($this->isUninitializedObject($x2->y));

        // proxy for Y is in identity map

        $z2 = $this->_em->createQuery('select z,y from ' . $z::class . ' z join z.y y where z.id = ?1')
                ->setParameter(1, $z->id)
                ->getSingleResult();
        self::assertFalse($this->isUninitializedObject($z2->y));
        self::assertEquals('Y', $z2->y->data);
        self::assertEquals($y->id, $z2->y->id);

        // since the Y is the same, the instance from the identity map is
        // used, even if it is a proxy.

        self::assertNotSame($x, $x2);
        self::assertNotSame($z, $z2);
        self::assertSame($z2->y, $x2->y);
    }
}


#[Table(name: 'ddc237_x')]
#[Entity]
class DDC237EntityX
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;
    /** @var string */
    #[Column(type: 'string', length: 255)]
    public $data;
    /** @var DDC237EntityY */
    #[OneToOne(targetEntity: 'DDC237EntityY')]
    #[JoinColumn(name: 'y_id', referencedColumnName: 'id')]
    public $y;
}


#[Table(name: 'ddc237_y')]
#[Entity]
class DDC237EntityY
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;
    /** @var string */
    #[Column(type: 'string', length: 255)]
    public $data;
}

#[Table(name: 'ddc237_z')]
#[Entity]
class DDC237EntityZ
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;
    /** @var string */
    #[Column(type: 'string', length: 255)]
    public $data;

    /** @var DDC237EntityY */
    #[OneToOne(targetEntity: 'DDC237EntityY')]
    #[JoinColumn(name: 'y_id', referencedColumnName: 'id')]
    public $y;
}
