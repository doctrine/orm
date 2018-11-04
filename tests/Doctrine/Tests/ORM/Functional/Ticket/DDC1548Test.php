<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-1548
 */
class DDC1548Test extends OrmFunctionalTestCase
{
    public function setUp() : void
    {
        parent::setUp();
        $this->schemaTool->createSchema(
            [
                $this->em->getClassMetadata(DDC1548E1::class),
                $this->em->getClassMetadata(DDC1548E2::class),
                $this->em->getClassMetadata(DDC1548Rel::class),
            ]
        );
    }

    public function testIssue() : void
    {
        $rel = new DDC1548Rel();
        $this->em->persist($rel);
        $this->em->flush();

        $e1      = new DDC1548E1();
        $e1->rel = $rel;
        $this->em->persist($e1);
        $this->em->flush();
        $this->em->clear();

        $obt = $this->em->find(DDC1548Rel::class, $rel->id);

        self::assertNull($obt->e2);
    }
}

/**
 * @ORM\Entity
 */
class DDC1548E1
{
    /**
     * @ORM\Id
     * @ORM\OneToOne(targetEntity=DDC1548Rel::class, inversedBy="e1")
     */
    public $rel;
}

/**
 * @ORM\Entity
 */
class DDC1548E2
{
    /**
     * @ORM\Id
     * @ORM\OneToOne(targetEntity=DDC1548Rel::class, inversedBy="e2")
     */
    public $rel;
}

/**
 * @ORM\Entity
 */
class DDC1548Rel
{
    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public $id;

    /** @ORM\OneToOne(targetEntity=DDC1548E1::class, mappedBy="rel") */
    public $e1;
    /** @ORM\OneToOne(targetEntity=DDC1548E2::class, mappedBy="rel") */
    public $e2;
}
