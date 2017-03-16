<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\Tests\Models\CMS\CmsUser;

/**
 * @group DDC-1452
 */
class DDC1452Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(
                [
                $this->_em->getClassMetadata(DDC1452EntityA::class),
                $this->_em->getClassMetadata(DDC1452EntityB::class),
                ]
            );
        } catch (\Exception $ignored) {
        }
    }

    public function testIssue()
    {
        $a1 = new DDC1452EntityA();
        $a1->title = "foo";

        $a2 = new DDC1452EntityA();
        $a2->title = "bar";

        $b = new DDC1452EntityB();
        $b->entityAFrom = $a1;
        $b->entityATo = $a2;

        $this->_em->persist($a1);
        $this->_em->persist($a2);
        $this->_em->persist($b);
        $this->_em->flush();
        $this->_em->clear();

        $dql = "SELECT a, b, ba FROM " . __NAMESPACE__ . "\DDC1452EntityA AS a LEFT JOIN a.entitiesB AS b LEFT JOIN b.entityATo AS ba";
        $results = $this->_em->createQuery($dql)->setMaxResults(1)->getResult();

        $this->assertSame($results[0], $results[0]->entitiesB[0]->entityAFrom);
        $this->assertFalse( $results[0]->entitiesB[0]->entityATo instanceof Proxy);
        $this->assertInstanceOf(Collection::class, $results[0]->entitiesB[0]->entityATo->getEntitiesB());
    }

    public function testFetchJoinOneToOneFromInverse()
    {
        $address = new \Doctrine\Tests\Models\CMS\CmsAddress();
        $address->city = "Bonn";
        $address->country = "Germany";
        $address->street = "Somestreet";
        $address->zip = 12345;

        $user = new CmsUser();
        $user->name = "beberlei";
        $user->username = "beberlei";
        $user->status = "active";
        $user->address = $address;
        $address->user = $user;

        $this->_em->persist($address);
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $dql = "SELECT a, u FROM Doctrine\Tests\Models\CMS\CmsAddress a INNER JOIN a.user u";
        $data = $this->_em->createQuery($dql)->getResult();
        $this->_em->clear();

        $this->assertFalse($data[0]->user instanceof Proxy);

        $dql = "SELECT u, a FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.address a";
        $data = $this->_em->createQuery($dql)->getResult();

        $this->assertFalse($data[0]->address instanceof Proxy);
    }
}

/**
 * @Entity
 */
class DDC1452EntityA
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
    /** @Column */
    public $title;
    /** @OneToMany(targetEntity="DDC1452EntityB", mappedBy="entityAFrom") */
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
 * @Entity
 */
class DDC1452EntityB
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /**
     * @ManyToOne(targetEntity="DDC1452EntityA", inversedBy="entitiesB")
     */
    public $entityAFrom;
    /**
     * @ManyToOne(targetEntity="DDC1452EntityA")
     */
    public $entityATo;
}
