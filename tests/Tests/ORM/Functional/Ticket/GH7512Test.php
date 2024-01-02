<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH7512Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH7512EntityA::class,
            GH7512EntityB::class,
            GH7512EntityC::class,
        ]);

        $this->_em->persist(new GH7512EntityA());
        $this->_em->persist(new GH7512EntityC());
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testFindEntityByAssociationPropertyJoinedChildWithClearMetadata(): void
    {
        // pretend we are starting afresh
        $this->_em = $this->getEntityManager();
        $result    = $this->_em->getRepository(GH7512EntityC::class)->findBy([
            'entityA' => new GH7512EntityB(),
        ]);
        $this->assertEmpty($result);
    }
}

/**
 * @Entity()
 * @InheritanceType("JOINED")
 * @DiscriminatorMap({
 *     "entitya"=GH7512EntityA::class,
 *     "entityB"=GH7512EntityB::class
 * })
 */
class GH7512EntityA
{
    /**
     * @Column(type="integer")
     * @Id()
     * @GeneratedValue(strategy="AUTO")
     * @var int
     */
    public $id;

    /**
     * @OneToMany(targetEntity="GH7512EntityC", mappedBy="entityA")
     * @var Collection<int, GH7512EntityC>
     */
    public $entityCs;
}

/** @Entity() */
class GH7512EntityB extends GH7512EntityA
{
}

/** @Entity() */
class GH7512EntityC
{
    /**
     * @Column(type="integer")
     * @Id()
     * @GeneratedValue(strategy="AUTO")
     * @var int
     */
    public $id;

    /**
     * @ManyToOne(targetEntity="GH7512EntityA", inversedBy="entityCs")
     * @var GH7512EntityA
     */
    public $entityA;
}
