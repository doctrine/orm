<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

use Doctrine\Tests\Models\Generic\DateTimeModel;

/**
 * @group DDC-657
 */
class DDC657Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    const NS = 'Doctrine\Tests\Models\Generic';

    protected function setUp()
    {
        $this->useModelSet('generic');
        parent::setUp();

        $this->loadFixtures();
    }

    public function testEntitySingleResult()
    {
        $query      = $this->_em->createQuery('SELECT d FROM ' . self::NS . '\DateTimeModel d');
        $datetime   = $query->setMaxResults(1)->getSingleResult();

        $this->assertInstanceOf('Doctrine\Tests\Models\Generic\DateTimeModel', $datetime);

        $this->assertInstanceOf('DateTime', $datetime->datetime);
        $this->assertInstanceOf('DateTime', $datetime->time);
        $this->assertInstanceOf('DateTime', $datetime->date);
    }

    public function testScalarResult()
    {
        $query      = $this->_em->createQuery('SELECT d.id, d.time, d.date, d.datetime FROM ' . self::NS . '\DateTimeModel d ORDER BY d.date ASC');
        $result     = $query->getScalarResult();

        $this->assertCount(2,$result);

        $this->assertContains('11:11:11', $result[0]['time']);
        $this->assertContains('2010-01-01', $result[0]['date']);
        $this->assertContains('2010-01-01 11:11:11', $result[0]['datetime']);

        $this->assertContains('12:12:12', $result[1]['time']);
        $this->assertContains('2010-02-02', $result[1]['date']);
        $this->assertContains('2010-02-02 12:12:12', $result[1]['datetime']);
    }

    public function testaTicketEntityArrayResult()
    {
        $query      = $this->_em->createQuery('SELECT d FROM ' . self::NS . '\DateTimeModel d ORDER BY d.date ASC');
        $result     = $query->getArrayResult();

        $this->assertCount(2,$result);

        $this->assertInstanceOf('DateTime', $result[0]['datetime']);
        $this->assertInstanceOf('DateTime', $result[0]['time']);
        $this->assertInstanceOf('DateTime', $result[0]['date']);

        $this->assertInstanceOf('DateTime', $result[1]['datetime']);
        $this->assertInstanceOf('DateTime', $result[1]['time']);
        $this->assertInstanceOf('DateTime', $result[1]['date']);
    }

    public function testTicketSingleResult()
    {
        $query      = $this->_em->createQuery('SELECT d.id, d.time, d.date, d.datetime FROM ' . self::NS . '\DateTimeModel d ORDER BY d.date ASC');
        $datetime   = $query->setMaxResults(1)->getSingleResult();

        $this->assertTrue(is_array($datetime));

        $this->assertInstanceOf('DateTime', $datetime['datetime']);
        $this->assertInstanceOf('DateTime', $datetime['time']);
        $this->assertInstanceOf('DateTime', $datetime['date']);
    }

    public function testTicketResult()
    {
        $query      = $this->_em->createQuery('SELECT d.id, d.time, d.date, d.datetime FROM ' . self::NS . '\DateTimeModel d ORDER BY d.date ASC');
        $result     = $query->getResult();

        $this->assertCount(2,$result);

        $this->assertInstanceOf('DateTime', $result[0]['time']);
        $this->assertInstanceOf('DateTime', $result[0]['date']);
        $this->assertInstanceOf('DateTime', $result[0]['datetime']);

        $this->assertEquals('2010-01-01 11:11:11', $result[0]['datetime']->format('Y-m-d G:i:s'));

        $this->assertInstanceOf('DateTime', $result[1]['time']);
        $this->assertInstanceOf('DateTime', $result[1]['date']);
        $this->assertInstanceOf('DateTime', $result[1]['datetime']);

        $this->assertEquals('2010-02-02 12:12:12', $result[1]['datetime']->format('Y-m-d G:i:s'));
    }

    public function loadFixtures()
    {
        $timezone           = new \DateTimeZone('America/Sao_Paulo');

        $dateTime1          = new DateTimeModel();
        $dateTime2          = new DateTimeModel();

        $dateTime1->date    = new \DateTime('2010-01-01', $timezone);
        $dateTime1->time    = new \DateTime('2010-01-01 11:11:11', $timezone);
        $dateTime1->datetime= new \DateTime('2010-01-01 11:11:11', $timezone);

        $dateTime2->date    = new \DateTime('2010-02-02', $timezone);
        $dateTime2->time    = new \DateTime('2010-02-02 12:12:12', $timezone);
        $dateTime2->datetime= new \DateTime('2010-02-02 12:12:12', $timezone);

        $this->_em->persist($dateTime1);
        $this->_em->persist($dateTime2);

        $this->_em->flush();
    }
}
