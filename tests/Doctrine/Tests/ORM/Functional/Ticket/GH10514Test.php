<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH10514Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            GH10514A::class,
            GH10514B::class,
            GH10514C::class
        );
    }

    public function testLoadEntityWhereIdentifierIsOneToOneAssociation(): void
    {
        $connection = $this->_em->getConnection();
        $connection->insert('A', ['id' => 1]);
        $connection->insert('B', ['id' => 1]);
        $connection->insert('C', ['id' => 1]);
        $connection->update('B', ['c_id' => 1], ['id' => 1]);

        $this->expectNotToPerformAssertions();

        $this->_em->find(GH10514B::class, 1);
    }
}

/**
 * @ORM\Entity()
 * @ORM\Table(name="A")
 */
class GH10514A
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    public $id;
}

/**
 * @ORM\Entity()
 * @ORM\Table(name="B")
 */
class GH10514B
{
    /**
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="GH10514A")
     * @ORM\JoinColumn(name="id", referencedColumnName="id")
     */
    public $a;

    /**
     * @ORM\OneToOne(targetEntity="GH10514C")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    public $c;
}

/**
 * @ORM\Entity()
 * @ORM\Table(name="C")
 */
class GH10514C
{
    /**
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="GH10514B")
     * @ORM\JoinColumn(name="id", referencedColumnName="id")
     */
    public $b;
}
