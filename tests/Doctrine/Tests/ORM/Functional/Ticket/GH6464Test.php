<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group GH-6464
 */
class GH6464Test extends OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp() : void
    {
        parent::setUp();

        $this->schemaTool->createSchema([
            $this->em->getClassMetadata(GH6464Post::class),
            $this->em->getClassMetadata(GH6464User::class),
            $this->em->getClassMetadata(GH6464Author::class),
        ]);
    }

    /**
     * Verifies that SqlWalker generates valid SQL for an INNER JOIN to CTI table
     *
     * SqlWalker needs to generate nested INNER JOIN statements, otherwise there would be INNER JOIN
     * statements without an ON clause, which are valid on e.g. MySQL but rejected by PostgreSQL.
     */
    public function testIssue() : void
    {
        $query = $this->em->createQueryBuilder()
            ->select('p')
            ->from(GH6464Post::class, 'p')
            ->innerJoin(GH6464Author::class, 'a', 'WITH', 'p.authorId = a.id')
            ->getQuery();

        self::assertNotRegExp(
            '/INNER JOIN \w+ \w+ INNER JOIN/',
            $query->getSQL(),
            'As of GH-6464, every INNER JOIN should have an ON clause, which is missing here'
        );

        // Query shouldn't yield a result, yet it shouldn't crash (anymore)
        self::assertEquals([], $query->getResult());
    }
}

/** @ORM\Entity */
class GH6464Post
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;

    /** @ORM\Column(type="integer") */
    public $authorId;
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"author" = "GH6464Author"})
 */
abstract class GH6464User
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
}

/** @ORM\Entity */
class GH6464Author extends GH6464User
{
}
