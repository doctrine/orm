<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\Models\CMS\CmsEmployee;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1250
 */
class DDC1250Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1250ClientHistory'),
            ));
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

        $this->_em->persist($c1);
        $this->_em->persist($c2);
        $this->_em->flush();
        $this->_em->clear();

        $history = $this->_em->createQuery('SELECT h FROM ' . __NAMESPACE__ . '\\DDC1250ClientHistory h WHERE h.id = ?1')
                  ->setParameter(1, $c2->id)->getSingleResult();

        $this->assertInstanceOf(__NAMESPACE__ . '\\DDC1250ClientHistory', $history);
    }
}

/**
 * @Entity
 */
class DDC1250ClientHistory
{
    /** @Id @GeneratedValue @Column(type="integer") */
    public $id;

    /** @OneToOne(targetEntity="DDC1250ClientHistory", inversedBy="declinedBy")
     * @JoinColumn(name="declined_clients_history_id", referencedColumnName="id")
     */
    public $declinedClientsHistory;

    /**
     * @OneToOne(targetEntity="DDC1250ClientHistory", mappedBy="declinedClientsHistory")
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