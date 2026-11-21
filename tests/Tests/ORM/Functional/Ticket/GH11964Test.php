<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

use function defined;

#[Group('GH11964')]
class GH11964Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(GH11964A::class, GH11964B::class, GH11964C::class, GH11964D::class);
    }

    public function testAssociationWithJoinColumnsInAnotherOrderThanTheTargetIdentifier(): void
    {
        $a = new GH11964A('A');
        $b = new GH11964B($a, 'B');

        foreach ([$a, $b, new GH11964C($b), new GH11964D($b)] as $entity) {
            $this->_em->persist($entity);
        }

        $this->_em->flush();
        $this->_em->clear();

        $b = $this->_em->find(GH11964B::class, ['a' => 'A', 'idB' => 'B']);

        foreach ([GH11964C::class, GH11964D::class] as $className) {
            $repository = $this->_em->getRepository($className);
            $criteria   = (defined(Criteria::class . '::ASC') ? Criteria::create(true) : Criteria::create())->where(Criteria::expr()->eq('b', $b));

            self::assertSame($b, $repository->findOneBy(['b' => $b])?->b, $className);
            self::assertCount(1, $repository->matching($criteria), $className);
        }
    }
}

#[ORM\Entity]
class GH11964A
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        public string $idA,
    ) {
    }
}

#[ORM\Entity]
class GH11964B
{
    public function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(name: 'id_a', referencedColumnName: 'idA')]
        public GH11964A $a,
        #[ORM\Id]
        #[ORM\Column]
        public string $idB,
    ) {
    }
}

/** Join columns in another order than the identifier of GH11964B */
#[ORM\Entity]
class GH11964C
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int|null $id = null;

    public function __construct(
        #[ORM\OneToOne]
        #[ORM\JoinColumn(name: 'id_b', referencedColumnName: 'idB')]
        #[ORM\JoinColumn(name: 'id_a', referencedColumnName: 'id_a')]
        public GH11964B $b,
    ) {
    }
}

/** Join columns in the order of the identifier of GH11964B */
#[ORM\Entity]
class GH11964D
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int|null $id = null;

    public function __construct(
        #[ORM\OneToOne]
        #[ORM\JoinColumn(name: 'id_a', referencedColumnName: 'id_a')]
        #[ORM\JoinColumn(name: 'id_b', referencedColumnName: 'idB')]
        public GH11964B $b,
    ) {
    }
}
