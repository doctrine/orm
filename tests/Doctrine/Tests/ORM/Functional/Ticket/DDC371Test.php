<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Query;
use Doctrine\Tests\OrmFunctionalTestCase;
use ProxyManager\Proxy\GhostObjectInterface;

/**
 * @group DDC-371
 */
class DDC371Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        //$this->em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $this->schemaTool->createSchema(
            [
                $this->em->getClassMetadata(DDC371Parent::class),
                $this->em->getClassMetadata(DDC371Child::class),
            ]
        );
    }

    public function testIssue() : void
    {
        $parent           = new DDC371Parent();
        $parent->data     = 'parent';
        $parent->children = new ArrayCollection();

        $child       = new DDC371Child();
        $child->data = 'child';

        $child->parent = $parent;
        $parent->children->add($child);

        $this->em->persist($parent);
        $this->em->persist($child);

        $this->em->flush();
        $this->em->clear();

        $children = $this->em->createQuery('select c,p from ' . __NAMESPACE__ . '\DDC371Child c '
                . 'left join c.parent p where c.id = 1 and p.id = 1')
                ->setHint(Query::HINT_REFRESH, true)
                ->getResult();

        self::assertCount(1, $children);
        self::assertNotInstanceOf(GhostObjectInterface::class, $children[0]->parent);
        self::assertFalse($children[0]->parent->children->isInitialized());
        self::assertEquals(0, $children[0]->parent->children->unwrap()->count());
    }
}

/** @ORM\Entity */
class DDC371Child
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    private $id;
    /** @ORM\Column(type="string") */
    public $data;
    /** @ORM\ManyToOne(targetEntity=DDC371Parent::class, inversedBy="children") @ORM\JoinColumn(name="parentId") */
    public $parent;
}

/** @ORM\Entity */
class DDC371Parent
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    private $id;
    /** @ORM\Column(type="string") */
    public $data;
    /** @ORM\OneToMany(targetEntity=DDC371Child::class, mappedBy="parent") */
    public $children;
}
