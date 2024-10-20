<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH10531Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            GH10531A::class,
            GH10531B::class,
        );
    }

    public function tearDown(): void
    {
        $conn = static::$sharedConn;
        $conn->executeStatement('DELETE FROM gh10531_b');
        $conn->executeStatement('DELETE FROM gh10531_a');
    }

    public function testInserts(): void
    {
        $a  = new GH10531A();
        $b1 = new GH10531B();
        $b2 = new GH10531B();
        $b3 = new GH10531B();

        $b1->parent = $b2;
        $b3->parent = $b2;
        $b2->parent = $a;

        /*
         * The following would force a working commit order, but that's not what
         * we want (the ORM shall sort this out internally).
         *
         * $this->_em->persist($a);
         * $this->_em->persist($b2);
         * $this->_em->flush();
         * $this->_em->persist($b1);
         * $this->_em->persist($b3);
         * $this->_em->flush();
         */

        // Pass $b2 to persist() between $b1 and $b3, so that any potential reliance upon the
        // order of persist() calls is spotted: No matter if it is in the order that persist()
        // was called or the other way round, in both cases there is an entity that will come
        // "before" $b2 but depend on its primary key, so the ORM must re-order the inserts.

        $this->_em->persist($a);
        $this->_em->persist($b1);
        $this->_em->persist($b2);
        $this->_em->persist($b3);
        $this->_em->flush();

        self::assertNotNull($a->id);
        self::assertNotNull($b1->id);
        self::assertNotNull($b2->id);
        self::assertNotNull($b3->id);
    }

    public function testDeletes(): void
    {
        $this->expectNotToPerformAssertions();
        $con = $this->_em->getConnection();

        // The "a" entity
        $con->insert('gh10531_a', ['id' => 1, 'discr' => 'A']);
        $a = $this->_em->find(GH10531A::class, 1);

        // The "b2" entity
        $con->insert('gh10531_a', ['id' => 2, 'discr' => 'B']);
        $con->insert('gh10531_b', ['id' => 2, 'parent_id' => 1]);
        $b2 = $this->_em->find(GH10531B::class, 2);

        // The "b1" entity
        $con->insert('gh10531_a', ['id' => 3, 'discr' => 'B']);
        $con->insert('gh10531_b', ['id' => 3, 'parent_id' => 2]);
        $b1 = $this->_em->find(GH10531B::class, 3);

        // The "b3" entity
        $con->insert('gh10531_a', ['id' => 4, 'discr' => 'B']);
        $con->insert('gh10531_b', ['id' => 4, 'parent_id' => 2]);
        $b3 = $this->_em->find(GH10531B::class, 4);

        /*
         * The following would make the deletions happen in an order
         * where the not-nullable foreign key constraints would not be
         * violated. But, we want the ORM to be able to sort this out
         * internally.
         *
         * $this->_em->remove($b1);
         * $this->_em->remove($b3);
         * $this->_em->remove($b2);
         */

        // As before, put $b2 in between $b1 and $b3 so that the order of the
        // remove() calls alone (in either direction) does not solve the problem.
        // The ORM will have to sort $b2 to be deleted last, after $b1 and $b3.
        $this->_em->remove($b1);
        $this->_em->remove($b2);
        $this->_em->remove($b3);

        $this->_em->flush();
    }
}

/**
 * We are using JTI here, since STI would relax the not-nullable constraint for the "parent"
 * column (it has to be NULL when the row contains a GH10531A instance). Causes another error,
 * but not the constraint violation I'd like to point out.
 */
#[ORM\Entity]
#[ORM\Table(name: 'gh10531_a')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['A' => GH10531A::class, 'B' => GH10531B::class])]
#[ORM\InheritanceType('JOINED')]
class GH10531A
{
    /** @var int */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    public $id;
}

#[ORM\Entity]
#[ORM\Table(name: 'gh10531_b')]
class GH10531B extends GH10531A
{
    /** @var GH10531A */
    #[ORM\ManyToOne(targetEntity: GH10531A::class)]
    #[ORM\JoinColumn(nullable: false, name: 'parent_id')]
    public $parent;
}
