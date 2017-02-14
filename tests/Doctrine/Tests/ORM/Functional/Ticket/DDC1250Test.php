<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;

/**
 * @group DDC-1250
 */
class DDC1250Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        try {
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(DDC1250ClientHistory::class),
                ]
            );
        } catch(\PDOException $e) {

        }
    }

    public function testIssue()
    {
        $c1 = new DDC1250ClientHistory;
        $c2 = new DDC1250ClientHistory;
        $c1->declinedClientsHistory = $c2;
        $c1->declinedBy = $c2;
        $c2->declinedBy = $c1;
        $c2->declinedClientsHistory= $c1;

        $this->em->persist($c1);
        $this->em->persist($c2);
        $this->em->flush();
        $this->em->clear();

        $history = $this->em->createQuery('SELECT h FROM ' . __NAMESPACE__ . '\\DDC1250ClientHistory h WHERE h.id = ?1')
                  ->setParameter(1, $c2->id)->getSingleResult();

        self::assertInstanceOf(DDC1250ClientHistory::class, $history);
    }
}

/**
 * @ORM\Entity
 */
class DDC1250ClientHistory
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    public $id;

    /** @ORM\OneToOne(targetEntity="DDC1250ClientHistory", inversedBy="declinedBy")
     * @ORM\JoinColumn(name="declined_clients_history_id", referencedColumnName="id")
     */
    public $declinedClientsHistory;

    /**
     * @ORM\OneToOne(targetEntity="DDC1250ClientHistory", mappedBy="declinedClientsHistory")
     * @var
     */
    public $declinedBy;
}

/**
 *
Entities\ClientsHistory:
type: entity
table: clients_history
fields:
id:
id: true
type: integer
unsigned: false
nullable: false
generator:
strategy: IDENTITY
[...skiped...]
oneToOne:
declinedClientsHistory:
targetEntity: Entities\ClientsHistory
joinColumn:
name: declined_clients_history_id
referencedColumnName: id
inversedBy: declinedBy
declinedBy:
targetEntity: Entities\ClientsHistory
mappedBy: declinedClientsHistory
lifecycleCallbacks: { }
repositoryClass: Entities\ClientsHistoryRepository


 */
