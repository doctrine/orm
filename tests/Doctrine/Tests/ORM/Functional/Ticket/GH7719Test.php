<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH7719Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        $this->setUpEntitySchema([GH7719Husband::class, GH7719Wife::class]);
    }

    public function testThatChangingTheEntityFromTheInverseSideInAOneToOneRelationDoesNotCauseUniqueIndexError()
    {
        $wife = new GH7719Wife();

        $wife->setHusband(new GH7719Husband());

        $this->em->persist($wife);
        $this->em->flush();

        $this->em->remove($wife->getHusband());
        $wife->setHusband(new GH7719Husband());

        $this->em->flush();
    }
}

/**
 * @ORM\Entity
 */
class GH7719Husband
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=GH7719Wife::class, inversedBy="husband")
     */
    private $wife;

    public function setWife(GH7719Wife $wife): void
    {
        $this->wife = $wife;
    }
}

/**
 * @ORM\Entity
 */
class GH7719Wife
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=GH7719Husband::class, mappedBy="wife", cascade={"persist"})
     */
    private $husband;

    public function setHusband(GH7719Husband $husband): void
    {
        $this->husband = $husband;
        $this->husband->setWife($this);
    }

    public function getHusband(): GH7719Husband
    {
        return $this->husband;
    }
}
