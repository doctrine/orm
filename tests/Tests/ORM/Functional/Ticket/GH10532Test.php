<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH10532Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            GH10532A::class,
            GH10532B::class,
            GH10532C::class,
            GH10532X::class,
        );
    }

    public function tearDown(): void
    {
        $conn = static::$sharedConn;
        $conn->executeStatement('DELETE FROM gh10532_c');
        $conn->executeStatement('DELETE FROM gh10532_b');
        $conn->executeStatement('DELETE FROM gh10532_a');
        $conn->executeStatement('DELETE FROM gh10532_x');
    }

    public function testInserts(): void
    {
        // Dependencies are $a1 -> $b -> $a2 -> $c

        $a1 = new GH10532A();
        $b  = new GH10532B();
        $a2 = new GH10532A();
        $c  = new GH10532C();

        $a1->x = $b;
        $b->a  = $a2;
        $a2->x = $c;

        /*
         * The following would force a working commit order, but that's not what
         * we want (the ORM shall sort this out internally).
         *
         * $this->_em->persist($c);
         * $this->_em->persist($a2);
         * $this->_em->flush();
         * $this->_em->persist($b);
         * $this->_em->persist($a1);
         * $this->_em->flush();
         */

        $this->_em->persist($a1);
        $this->_em->persist($a2);
        $this->_em->persist($b);
        $this->_em->persist($c);
        $this->_em->flush();

        self::assertNotNull($a1->id);
        self::assertNotNull($b->id);
        self::assertNotNull($a2->id);
        self::assertNotNull($c->id);
    }

    public function testDeletes(): void
    {
        // Dependencies are $a1 -> $b -> $a2 -> $c

        $this->expectNotToPerformAssertions();
        $con = $this->_em->getConnection();

        // The "c" entity
        $con->insert('gh10532_x', ['id' => 1, 'discr' => 'C']);
        $con->insert('gh10532_c', ['id' => 1]);
        $c = $this->_em->find(GH10532C::class, 1);

        // The "a2" entity
        $con->insert('gh10532_a', ['id' => 2, 'gh10532x_id' => 1]);
        $a2 = $this->_em->find(GH10532A::class, 2);

        // The "b" entity
        $con->insert('gh10532_x', ['id' => 3, 'discr' => 'B']);
        $con->insert('gh10532_b', ['id' => 3, 'gh10532a_id' => 2]);
        $b = $this->_em->find(GH10532B::class, 3);

        // The "a1" entity
        $con->insert('gh10532_a', ['id' => 4, 'gh10532x_id' => 3]);
        $a1 = $this->_em->find(GH10532A::class, 4);

        /*
         * The following would make the deletions happen in an order
         * where the not-nullable foreign key constraints would not be
         * violated. But, we want the ORM to be able to sort this out
         * internally.
         *
         * $this->_em->remove($a1);
         * $this->_em->flush();
         * $this->_em->remove($b);
         * $this->_em->flush();
         * $this->_em->remove($a2);
         * $this->_em->remove($c);
         * $this->_em->flush();
         */

        $this->_em->remove($a1);
        $this->_em->remove($a2);
        $this->_em->remove($b);
        $this->_em->remove($c);

        $this->_em->flush();
    }
}

/**
 * We are using JTI here, since STI would relax the not-nullable constraint for the "parent"
 * column. Causes another error, but not the constraint violation I'd like to point out.
 */
#[ORM\Entity]
#[ORM\Table(name: 'gh10532_x')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['B' => GH10532B::class, 'C' => GH10532C::class])]
#[ORM\InheritanceType('JOINED')]
abstract class GH10532X
{
    /** @var int */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    public $id;
}

#[ORM\Entity]
#[ORM\Table(name: 'gh10532_b')]
class GH10532B extends GH10532X
{
    /** @var GH10532A */
    #[ORM\ManyToOne(targetEntity: GH10532A::class)]
    #[ORM\JoinColumn(nullable: false, name: 'gh10532a_id')]
    public $a;
}

#[ORM\Entity]
#[ORM\Table(name: 'gh10532_c')]
class GH10532C extends GH10532X
{
}

#[ORM\Entity]
#[ORM\Table(name: 'gh10532_a')]
class GH10532A
{
    /** @var int */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    public $id;

    /** @var GH10532X */
    #[ORM\ManyToOne(targetEntity: GH10532X::class)]
    #[ORM\JoinColumn(nullable: false, name: 'gh10532x_id')]
    public $x;
}
