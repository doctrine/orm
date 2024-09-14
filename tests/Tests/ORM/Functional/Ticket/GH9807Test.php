<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Internal\Hydration\ObjectHydrator;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\Mocks\ArrayResultFactory;
use Doctrine\Tests\OrmFunctionalTestCase;

final class GH9807Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(GH9807Main::class, GH9807Join::class);
    }

    public function testHydrateJoinedCollectionWithFirstNullishRow(): void
    {
        $rsm = new ResultSetMapping();
        $rsm->addEntityResult(GH9807Main::class, 'm');
        $rsm->addJoinedEntityResult(GH9807Join::class, 'j', 'm', 'joins');

        $rsm->addFieldResult('m', 'id_0', 'id');
        $rsm->addFieldResult('j', 'id_1', 'id');
        $rsm->addFieldResult('j', 'value_2', 'value');

        $hydrator = new ObjectHydrator($this->_em);

        $uow = $this->_em->getUnitOfWork();

        $uow->createEntity(
            GH9807Main::class,
            ['id' => 1]
        );

        $resultSet = [
            [
                'id_0' => 1,
                'id_1' => null,
                'value_2' => null,
            ],
            [
                'id_0' => 1,
                'id_1' => 1,
                'value_2' => '2',
            ],
            [
                'id_0' => 1,
                'id_1' => 2,
                'value_2' => '2',
            ],
        ];

        $stmt = ArrayResultFactory::createFromArray($resultSet);

        /** @var GH9807Main[] $result */
        $result = $hydrator->hydrateAll($stmt, $rsm);

        self::assertInstanceOf(GH9807Main::class, $result[0]);
        self::assertCount(2, $result[0]->getJoins());
    }
}

/** @Entity */
class GH9807Main
{
    /**
     * @var int
     * @Column(type="integer")
     * @Id
     * @GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToMany(targetEntity="GH9807Join", inversedBy="starts")
     *
     * @var Collection<int, GH9807Join>
     */
    private $joins;

    /** @return Collection<int, GH9807Join> */
    public function getJoins(): Collection
    {
        return $this->joins;
    }
}

/** @Entity */
class GH9807Join
{
    /**
     * @var int
     * @Column(type="integer")
     * @Id
     * @GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToMany(targetEntity="GH9807Main", mappedBy="bases")
     *
     * @var Collection<int, GH9807Main>
     */
    private $mains;

    /**
     * @ORM\Column(type="string", nullable=false)
     *
     * @var string
     */
    private $value;
}
