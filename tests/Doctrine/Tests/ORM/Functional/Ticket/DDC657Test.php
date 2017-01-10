<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Generic\DateTimeModel;

/**
 * @group DDC-657
 */
class DDC657Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('generic');
        parent::setUp();

        $this->loadFixtures();
    }

    public function testEntitySingleResult()
    {
        $query      = $this->em->createQuery('SELECT d FROM ' . DateTimeModel::class . ' d');
        $datetime   = $query->setMaxResults(1)->getSingleResult();

        self::assertInstanceOf(DateTimeModel::class, $datetime);

        self::assertInstanceOf('DateTime', $datetime->datetime);
        self::assertInstanceOf('DateTime', $datetime->time);
        self::assertInstanceOf('DateTime', $datetime->date);
    }

    public function testScalarResult()
    {
        $query      = $this->em->createQuery('SELECT d.id, d.time, d.date, d.datetime FROM ' . DateTimeModel::class . ' d ORDER BY d.date ASC');
        $result     = $query->getScalarResult();

        self::assertCount(2,$result);

        self::assertContains('11:11:11', $result[0]['time']);
        self::assertContains('2010-01-01', $result[0]['date']);
        self::assertContains('2010-01-01 11:11:11', $result[0]['datetime']);

        self::assertContains('12:12:12', $result[1]['time']);
        self::assertContains('2010-02-02', $result[1]['date']);
        self::assertContains('2010-02-02 12:12:12', $result[1]['datetime']);
    }

    public function testaTicketEntityArrayResult()
    {
        $query      = $this->em->createQuery('SELECT d FROM ' . DateTimeModel::class . ' d ORDER BY d.date ASC');
        $result     = $query->getArrayResult();

        self::assertCount(2,$result);

        self::assertInstanceOf('DateTime', $result[0]['datetime']);
        self::assertInstanceOf('DateTime', $result[0]['time']);
        self::assertInstanceOf('DateTime', $result[0]['date']);

        self::assertInstanceOf('DateTime', $result[1]['datetime']);
        self::assertInstanceOf('DateTime', $result[1]['time']);
        self::assertInstanceOf('DateTime', $result[1]['date']);
    }

    public function testTicketSingleResult()
    {
        $query      = $this->em->createQuery('SELECT d.id, d.time, d.date, d.datetime FROM ' . DateTimeModel::class . ' d ORDER BY d.date ASC');
        $datetime   = $query->setMaxResults(1)->getSingleResult();

        self::assertTrue(is_array($datetime));

        self::assertInstanceOf('DateTime', $datetime['datetime']);
        self::assertInstanceOf('DateTime', $datetime['time']);
        self::assertInstanceOf('DateTime', $datetime['date']);
    }

    public function testTicketResult()
    {
        $query      = $this->em->createQuery('SELECT d.id, d.time, d.date, d.datetime FROM ' . DateTimeModel::class . ' d ORDER BY d.date ASC');
        $result     = $query->getResult();

        self::assertCount(2,$result);

        self::assertInstanceOf('DateTime', $result[0]['time']);
        self::assertInstanceOf('DateTime', $result[0]['date']);
        self::assertInstanceOf('DateTime', $result[0]['datetime']);

        self::assertEquals('2010-01-01 11:11:11', $result[0]['datetime']->format('Y-m-d G:i:s'));

        self::assertInstanceOf('DateTime', $result[1]['time']);
        self::assertInstanceOf('DateTime', $result[1]['date']);
        self::assertInstanceOf('DateTime', $result[1]['datetime']);

        self::assertEquals('2010-02-02 12:12:12', $result[1]['datetime']->format('Y-m-d G:i:s'));
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

        $this->em->persist($dateTime1);
        $this->em->persist($dateTime2);

        $this->em->flush();
    }
}
