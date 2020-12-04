<?php
declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Exception\SyntaxErrorException;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\Tests\OrmFunctionalTestCase;

final class GH8365Test extends OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp() : void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH8365Entity::class,
        ]);
    }

    protected function generateMockEntity(int $level = 0): GH8365Entity
    {
        if ($level >= 3) {
            return (new GH8365Entity());
        }
        return (new GH8365Entity())->setChildren(new ArrayCollection([
            $this->generateMockEntity($level + 1),
            $this->generateMockEntity($level + 1),
            $this->generateMockEntity($level + 1),
        ]));
    }

    public function testBindingEntityParameter() : void
    {
        $entity = $this->generateMockEntity();
        $this->_em->persist($entity);
        $this->_em->flush();

        $qb = $this->_em->getRepository(GH8365Entity::class)->createQueryBuilder('a');
        $query = $qb->select('a.id')
            ->andWhere($qb->expr()->eq('a.parent', ':parent'))
            ->setParameter('parent', $entity)
            ->getQuery()
        ;
        $this->assertSQLEquals(
            'SELECT g0_.id AS id_0 FROM GH8365Entity g0_ WHERE g0_.parent_id = ?',
            $query->getSQL()
        );

        /*
         * Doctrine\DBAL\Exception\SyntaxErrorException:
         * An exception occurred while executing
         * 'SELECT g0_.id AS id_0 FROM GH8365Entity g0_ WHERE g0_.parent_id = ?, ?, ?'
         * with params [false, false, false]:
         */
        try{
            $result = $query->getScalarResult();
        } catch (SyntaxErrorException $e) {
            $this->fail(sprintf("SyntaxErrorException: \n\"%s\" \nthrows: \n\"%s\"",
                $query->getSQL(),
                $e->getMessage()
            ));
        }
    }
}

/**
 * @Entity
 */
final class GH8365Entity implements \IteratorAggregate
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;
    /**
     * @ManyToOne(targetEntity="GH8365Entity", inversedBy="children")
     * @var GH8365Entity
     */
    public $parent;
    /**
     * @var ArrayCollection
     * @OneToMany(targetEntity="GH8365Entity", mappedBy="parent", cascade={"all"})
     * @var Collection<GH8365Entity>
     */
    public $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getIterator()
    {
        return $this->children->getIterator();
    }

    /**
     * @param Collection<GH8365Entity> $children
     * @return GH8365Entity
     */
    public function setChildren(Collection $children)
    {
        $this->children = $children;
        return $this;
    }
}