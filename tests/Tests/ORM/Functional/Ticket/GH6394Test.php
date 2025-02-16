<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Version;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

class GH6394Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(A::class, B::class);
    }

    /**
     * Test the the version of an entity can be fetched, when the id field and
     * the id column are different.
     */
    #[Group('6393')]
    public function testFetchVersionValueForDifferentIdFieldAndColumn(): void
    {
        $a = new A(1);
        $this->_em->persist($a);

        $b = new B($a, 'foo');
        $this->_em->persist($b);
        $this->_em->flush();

        self::assertSame(1, $b->version);

        $b->something = 'bar';
        $this->_em->flush();

        self::assertSame(2, $b->version);
    }
}

#[Entity]
class A
{
    /** @var int */
    #[Version]
    #[Column(type: 'integer')]
    public $version;

    public function __construct(
        #[Id]
        #[Column(type: 'integer')]
        public int $id,
    ) {
    }
}

#[Entity]
class B
{
    /** @var int */
    #[Version]
    #[Column(type: 'integer')]
    public $version;

    public function __construct(
        #[Id]
        #[ManyToOne(targetEntity: 'A')]
        #[JoinColumn(name: 'aid', referencedColumnName: 'id')]
        public A $a,
        #[Column(type: 'string', length: 255)]
        public string $something,
    ) {
    }
}
