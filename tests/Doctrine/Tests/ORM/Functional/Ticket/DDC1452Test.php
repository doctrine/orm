<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;
use ProxyManager\Proxy\GhostObjectInterface;

/**
 * @group DDC-1452
 */
class DDC1452Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        $this->useModelSet('cms');
        parent::setUp();

        try {
            $this->schemaTool->createSchema(
                [
                    $this->em->getClassMetadata(DDC1452EntityA::class),
                    $this->em->getClassMetadata(DDC1452EntityB::class),
                ]
            );
        } catch (Exception $ignored) {
        }
    }

    public function testIssue() : void
    {
        $a1        = new DDC1452EntityA();
        $a1->title = 'foo';

        $a2        = new DDC1452EntityA();
        $a2->title = 'bar';

        $b              = new DDC1452EntityB();
        $b->entityAFrom = $a1;
        $b->entityATo   = $a2;

        $this->em->persist($a1);
        $this->em->persist($a2);
        $this->em->persist($b);
        $this->em->flush();
        $this->em->clear();

        $dql     = 'SELECT a, b, ba FROM ' . __NAMESPACE__ . '\DDC1452EntityA AS a LEFT JOIN a.entitiesB AS b LEFT JOIN b.entityATo AS ba';
        $results = $this->em->createQuery($dql)->setMaxResults(1)->getResult();

        self::assertSame($results[0], $results[0]->entitiesB[0]->entityAFrom);
        self::assertNotInstanceOf(GhostObjectInterface::class, $results[0]->entitiesB[0]->entityATo);
        self::assertInstanceOf(Collection::class, $results[0]->entitiesB[0]->entityATo->getEntitiesB());
    }

    public function testFetchJoinOneToOneFromInverse() : void
    {
        $address          = new CmsAddress();
        $address->city    = 'Bonn';
        $address->country = 'Germany';
        $address->street  = 'Somestreet';
        $address->zip     = 12345;

        $user           = new CmsUser();
        $user->name     = 'beberlei';
        $user->username = 'beberlei';
        $user->status   = 'active';
        $user->address  = $address;
        $address->user  = $user;

        $this->em->persist($address);
        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        $dql  = 'SELECT a, u FROM Doctrine\Tests\Models\CMS\CmsAddress a INNER JOIN a.user u';
        $data = $this->em->createQuery($dql)->getResult();
        $this->em->clear();

        self::assertNotInstanceOf(GhostObjectInterface::class, $data[0]->user);

        $dql  = 'SELECT u, a FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.address a';
        $data = $this->em->createQuery($dql)->getResult();

        self::assertNotInstanceOf(GhostObjectInterface::class, $data[0]->address);
    }
}

/**
 * @ORM\Entity
 */
class DDC1452EntityA
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
    /** @ORM\Column */
    public $title;
    /** @ORM\OneToMany(targetEntity=DDC1452EntityB::class, mappedBy="entityAFrom") */
    public $entitiesB;

    public function __construct()
    {
        $this->entitiesB = new ArrayCollection();
    }

    public function getEntitiesB()
    {
        return $this->entitiesB;
    }
}

/**
 * @ORM\Entity
 */
class DDC1452EntityB
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;

    /** @ORM\ManyToOne(targetEntity=DDC1452EntityA::class, inversedBy="entitiesB") */
    public $entityAFrom;
    /** @ORM\ManyToOne(targetEntity=DDC1452EntityA::class) */
    public $entityATo;
}
