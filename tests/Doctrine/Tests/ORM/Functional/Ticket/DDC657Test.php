<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

use Doctrine\Tests\Models\Generic\DateTimeModel;

/**
 * @group DDC-657
 */
class DDC657Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    const  NS = 'Doctrine\Tests\Models\Generic';

    protected function setUp()
    {
        $this->useModelSet('generic');
        parent::setUp();

        $this->loadFixtures();
    }

    public function testEntitySingleResult()
    {
        $query      = $this->_em->createQuery('SELECT d FROM ' . self::NS . '\DateTimeModel d');
        $datetime   = $query->getSingleResult();

        $this->assertTrue($datetime instanceof DateTimeModel);

        $this->assertTrue($datetime->datetime instanceof \DateTime);
        $this->assertTrue($datetime->time instanceof \DateTime);
        $this->assertTrue($datetime->date instanceof \DateTime);
    }
    
    public function testEntityArrayResult()
    {
        $query      = $this->_em->createQuery('SELECT d FROM ' . self::NS . '\DateTimeModel d');
        $result     = $query->getArrayResult();
        $datetime   = $result[0];

        $this->assertTrue(is_array($datetime));

        $this->assertTrue($datetime['datetime'] instanceof \DateTime);
        $this->assertTrue($datetime['time'] instanceof \DateTime);
        $this->assertTrue($datetime['date'] instanceof \DateTime);
    }

    public function testTicketSingleResult()
    {
        $query      = $this->_em->createQuery('SELECT d.id, d.time, d.date, d.datetime FROM ' . self::NS . '\DateTimeModel d');
        $datetime   = $query->getSingleResult();

        $this->assertTrue(is_array($datetime));

        $this->assertTrue($datetime['datetime'] instanceof \DateTime);
        $this->assertTrue($datetime['time'] instanceof \DateTime);
        $this->assertTrue($datetime['date'] instanceof \DateTime);
    }

    public function loadFixtures()
    {
        $timezone           = new \DateTimeZone('America/Sao_Paulo');
        $dateTime           = new DateTimeModel();
        
        $dateTime->date     = new \DateTime('2012-01-06', $timezone);
        $dateTime->time     = new \DateTime('2012-01-06 10:11:12', $timezone);
        $dateTime->datetime = new \DateTime('2012-01-06 09:10:11', $timezone);
        
        $this->_em->persist($dateTime);

        $this->_em->flush();
    }

}