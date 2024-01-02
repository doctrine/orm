<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('GH6499')]
class GH6499OneToOneRelationshipTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(GH6499OTOA::class, GH6499OTOB::class);
    }

    /**
     * Test for the bug described in issue #6499.
     */
    public function testIssue(): void
    {
        $a = new GH6499OTOA();

        $this->_em->persist($a);
        $this->_em->flush();
        $this->_em->clear();

        self::assertEquals(
            $this->_em->find(GH6499OTOA::class, $a->id)->b->id,
            $a->b->id,
            'Issue #6499 will result in an integrity constraint violation before reaching this point.',
        );
    }
}

#[ORM\Entity]
class GH6499OTOA
{
    /** @var int */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public $id;

    /** @var GH6499OTOB */
    #[ORM\OneToOne(targetEntity: GH6499OTOB::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    public $b;

    public function __construct()
    {
        $this->b = new GH6499OTOB();
    }
}

#[ORM\Entity]
class GH6499OTOB
{
    /** @var int */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public $id;
}
