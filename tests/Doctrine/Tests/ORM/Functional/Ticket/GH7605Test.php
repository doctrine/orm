<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH7605Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        $this->schemaTool->createSchema(
            [
                $this->em->getClassMetadata(GH7605Offer::class),
            ]
        );
    }

    /**
     * The intent of this test is to ensure that the ORM is capable
     * of generate queries using both ComparisonExpression and InExpression
     * when the left operand is a CASE statement
     *
     * @group 7605
     */
    public function testCaseAsLeftOperandOfComparisonAndInExpression() : void
    {
        // Rules to define offer status (the order matters):
        // 1 - active === false? INACTIVE
        // 2 - closed === true?  CLOSED
        // 3 - balance > 0?    ACTIVE
        // 4 - balance === 0?  FINISHED
        $offersInfo = array(
            // The fields order is: id, active, closed, balance
            array(1, true, false, 10), // ACTIVE
            array(2, true, false, 0), // FINISHED
            array(3, false, true, 30), // INACTIVE
            array(4, true, true, 20), // CLOSED
        );
        foreach ($offersInfo as $offerInfo) {
            $offer = new GH7605Offer($offerInfo[0], $offerInfo[1], $offerInfo[2], $offerInfo[3]);
            $this->em->persist($offer);
        }
        $this->em->flush();
        $this->em->clear();
        
        $dqlStatusLogic = "CASE 
            WHEN offer.active = FALSE THEN 'INACTIVE'
            WHEN offer.closed = TRUE THEN 'CLOSED'
            WHEN offer.balance > 0 THEN 'ACTIVE'
            ELSE 'FINISHED'
        END";
        
        // ComparisonExpression tests
        $query = $this->em->createQueryBuilder()
            ->select('offer')
            ->from(GH7605Offer::class, 'offer')
            ->where($dqlStatusLogic.' = :status')
            ->setMaxResults(1)
            ->getQuery();
        
        $query->setParameter('status', 'ACTIVE');
        $offer = $query->getOneOrNullResult(AbstractQuery::HYDRATE_OBJECT);
        self::assertEquals(1, $offer->id);
        
        $query->setParameter('status', 'INACTIVE');
        $offer = $query->getOneOrNullResult(AbstractQuery::HYDRATE_OBJECT);
        self::assertEquals(3, $offer->id);
        
        // InExpression tests
        $qb = $this->em->createQueryBuilder()
            ->select('offer')
            ->from(GH7605Offer::class, 'offer')
            ->where($dqlStatusLogic.' IN (:status)')
            ->orderBy('offer.id', 'ASC');
        $query = $qb->getQuery();
        $query->setParameter('status', array('INACTIVE', 'CLOSED'));
        $offers = $query->getResult(AbstractQuery::HYDRATE_OBJECT);
        $expectedIds = [3, 4];
        foreach ($offers as $i => $offer) {
            self::assertEquals($expectedIds[$i], $offer->id);
        }
        
        $query = $qb->where($dqlStatusLogic.' NOT IN (:status)')->getQuery();
        $query->setParameter('status', array('FINISHED', 'INACTIVE'));
        $offers = $query->getResult(AbstractQuery::HYDRATE_OBJECT);
        $expectedIds = [1, 4];
        foreach ($offers as $i => $offer) {
            self::assertEquals($expectedIds[$i], $offer->id);
        }
    }
}

/**
 * @ORM\Entity
 */
class GH7605Offer
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public $id;

    /** @ORM\Column(type="boolean", nullable=false) */
    public $active;

    /** @ORM\Column(type="boolean", nullable=false) */
    public $closed;

    /** @ORM\Column(type="integer", nullable=false) */
    public $balance;

    /**
     * @param string $name
     */
    public function __construct($id, $active, $closed, $balance)
    {
        $this->id = $id;
        $this->active = $active;
        $this->closed = $closed;
        $this->balance = $balance;
    }
}
