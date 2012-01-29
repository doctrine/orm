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

        $this->assertTrue($datetime instanceof DateTimeModel);

        $this->assertTrue($datetime->datetime instanceof \DateTime);
        $this->assertTrue($datetime->time instanceof \DateTime);
        $this->assertTrue($datetime->date instanceof \DateTime);
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

        $this->assertTrue($result[0]['datetime'] instanceof \DateTime);
        $this->assertTrue($result[0]['time'] instanceof \DateTime);
        $this->assertTrue($result[0]['date'] instanceof \DateTime);

        $this->assertTrue($result[1]['datetime'] instanceof \DateTime);
        $this->assertTrue($result[1]['time'] instanceof \DateTime);
        $this->assertTrue($result[1]['date'] instanceof \DateTime);
    }

    public function testTicketSingleResult()
    {
        $query      = $this->_em->createQuery('SELECT d.id, d.time, d.date, d.datetime FROM ' . self::NS . '\DateTimeModel d ORDER BY d.date ASC');
        $datetime   = $query->setMaxResults(1)->getSingleResult();

        $this->assertTrue(is_array($datetime));

        $this->assertTrue($datetime['datetime'] instanceof \DateTime);
        $this->assertTrue($datetime['time'] instanceof \DateTime);
        $this->assertTrue($datetime['date'] instanceof \DateTime);
    }

    public function testTicketResult()
    {
        $query      = $this->_em->createQuery('SELECT d.id, d.time, d.date, d.datetime FROM ' . self::NS . '\DateTimeModel d ORDER BY d.date ASC');
        $result     = $query->getResult();

        $this->assertCount(2,$result);

        $this->assertTrue($result[0]['time'] instanceof \DateTime);
        $this->assertTrue($result[0]['date'] instanceof \DateTime);
        $this->assertTrue($result[0]['datetime'] instanceof \DateTime);
        $this->assertEquals('2010-01-01 11:11:11', $result[0]['datetime']->format('Y-m-d G:i:s'));

        $this->assertTrue($result[1]['time'] instanceof \DateTime);
        $this->assertTrue($result[1]['date'] instanceof \DateTime);
        $this->assertTrue($result[1]['datetime'] instanceof \DateTime);
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
