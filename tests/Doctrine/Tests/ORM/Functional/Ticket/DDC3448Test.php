<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

class DDC3448Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(DDC3448Client::class),
            $this->_em->getClassMetadata(DDC3448Target::class),
        ));
    }

    public function testOrderedEagerAssociationShouldBeOrdered()
    {
        $client = new DDC3448Client();
        $this->_em->persist($client);

        $positions = range(0, 99);
        shuffle($positions);

        foreach ($positions as $pos) {
            $target = new DDC3448Target($client, $pos);
            $this->_em->persist($target);
        }

        $this->_em->flush();
        $id = $client->id;

        $this->_em->clear();

        $dbClient = $this->_em->find(DDC3448Client::class, $id);

        $initial = -1;
        foreach ($dbClient->targets as $target) {
            $this->assertGreaterThan($initial, $target->position);
            $initial = $target->position;
        }
    }
}

/**
 * @Entity
 * @Table(name="ddc3448_clients")
 */
class DDC3448Client
{
    /**
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @OneToMany(targetEntity="DDC3448Target", mappedBy="client", fetch="EAGER")
     * @OrderBy({"position" = "ASC"})

     *
     * @var DDC3448Target[]
     */
    public $targets;
}

/**
 * @Entity
 * @Table(name="ddc3448_targets")
 */
class DDC3448Target
{
    /**
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Column(name="position", type="integer")
     * @var int
     */
    public $position;

    /**
     * @ManyToOne(targetEntity="DDC3448Client", inversedBy="targets")
     *
     * @var DDC3448Client
     */
    private $client;

    /**
     * @param DDC3448Client $client
     * @param int $position
     */
    public function __construct(DDC3448Client $client, $position)
    {
        $this->position = $position;
        $this->client = $client;
    }
}
