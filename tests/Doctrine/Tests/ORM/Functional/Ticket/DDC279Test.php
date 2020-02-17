<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC279Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        $this->schemaTool->createSchema(
            [
                $this->em->getClassMetadata(DDC279EntityXAbstract::class),
                $this->em->getClassMetadata(DDC279EntityX::class),
                $this->em->getClassMetadata(DDC279EntityY::class),
                $this->em->getClassMetadata(DDC279EntityZ::class),
            ]
        );
    }

    /**
     * @group DDC-279
     */
    public function testDDC279() : void
    {
        $x = new DDC279EntityX();
        $y = new DDC279EntityY();
        $z = new DDC279EntityZ();

        $x->data = 'X';
        $y->data = 'Y';
        $z->data = 'Z';

        $x->y = $y;
        $y->z = $z;

        $this->em->persist($x);
        $this->em->persist($y);
        $this->em->persist($z);

        $this->em->flush();
        $this->em->clear();

        $query = $this->em->createQuery(
            'SELECT x, y, z FROM Doctrine\Tests\ORM\Functional\Ticket\DDC279EntityX x ' .
            'INNER JOIN x.y y INNER JOIN y.z z WHERE x.id = ?1'
        )->setParameter(1, $x->id);

        $result = $query->getResult();

        $expected1 = 'Y';
        $expected2 = 'Z';

        self::assertCount(1, $result);

        self::assertEquals($expected1, $result[0]->y->data);
        self::assertEquals($expected2, $result[0]->y->z->data);
    }
}


/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"DDC279EntityX" = DDC279EntityX::class})
 */
abstract class DDC279EntityXAbstract
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="integer")
     */
    public $id;

    /** @ORM\Column(type="string") */
    public $data;
}

/**
 * @ORM\Entity
 */
class DDC279EntityX extends DDC279EntityXAbstract
{
    /**
     * @ORM\OneToOne(targetEntity=DDC279EntityY::class)
     * @ORM\JoinColumn(name="y_id", referencedColumnName="id")
     */
    public $y;
}

/**
 * @ORM\Entity
 */
class DDC279EntityY
{
    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(name="id", type="integer")
     */
    public $id;

    /** @ORM\Column(type="string") */
    public $data;

    /**
     * @ORM\OneToOne(targetEntity=DDC279EntityZ::class)
     * @ORM\JoinColumn(name="z_id", referencedColumnName="id")
     */
    public $z;
}

/**
 * @ORM\Entity
 */
class DDC279EntityZ
{
    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(name="id", type="integer")
     */
    public $id;

    /** @ORM\Column(type="string") */
    public $data;
}
