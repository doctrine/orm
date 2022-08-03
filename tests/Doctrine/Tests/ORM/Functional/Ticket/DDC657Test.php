<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use DateTime;
use DateTimeZone;
use Doctrine\Tests\Models\Generic\DateTimeModel;
use Doctrine\Tests\OrmFunctionalTestCase;

use function is_array;

/**
 * @group DDC-657
 */
class DDC657Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('generic');
        parent::setUp();

        $this->loadFixtures();
    }

    public function testEntitySingleResult(): void
    {
        $query    = $this->_em->createQuery('SELECT d FROM ' . DateTimeModel::class . ' d');
        $datetime = $query->setMaxResults(1)->getSingleResult();

        $this->assertInstanceOf(DateTimeModel::class, $datetime);

        $this->assertInstanceOf('DateTime', $datetime->datetime);
        $this->assertInstanceOf('DateTime', $datetime->time);
        $this->assertInstanceOf('DateTime', $datetime->date);
    }

    public function testScalarResult(): void
    {
        $query  = $this->_em->createQuery('SELECT d.id, d.time, d.date, d.datetime FROM ' . DateTimeModel::class . ' d ORDER BY d.date ASC');
        $result = $query->getScalarResult();

        $this->assertCount(2, $result);

        $this->assertStringContainsString('11:11:11', $result[0]['time']);
        $this->assertStringContainsString('2010-01-01', $result[0]['date']);
        $this->assertStringContainsString('2010-01-01 11:11:11', $result[0]['datetime']);

        $this->assertStringContainsString('12:12:12', $result[1]['time']);
        $this->assertStringContainsString('2010-02-02', $result[1]['date']);
        $this->assertStringContainsString('2010-02-02 12:12:12', $result[1]['datetime']);
    }

    public function testaTicketEntityArrayResult(): void
    {
        $query  = $this->_em->createQuery('SELECT d FROM ' . DateTimeModel::class . ' d ORDER BY d.date ASC');
        $result = $query->getArrayResult();

        $this->assertCount(2, $result);

        $this->assertInstanceOf('DateTime', $result[0]['datetime']);
        $this->assertInstanceOf('DateTime', $result[0]['time']);
        $this->assertInstanceOf('DateTime', $result[0]['date']);

        $this->assertInstanceOf('DateTime', $result[1]['datetime']);
        $this->assertInstanceOf('DateTime', $result[1]['time']);
        $this->assertInstanceOf('DateTime', $result[1]['date']);
    }

    public function testTicketSingleResult(): void
    {
        $query    = $this->_em->createQuery('SELECT d.id, d.time, d.date, d.datetime FROM ' . DateTimeModel::class . ' d ORDER BY d.date ASC');
        $datetime = $query->setMaxResults(1)->getSingleResult();

        $this->assertTrue(is_array($datetime));

        $this->assertInstanceOf('DateTime', $datetime['datetime']);
        $this->assertInstanceOf('DateTime', $datetime['time']);
        $this->assertInstanceOf('DateTime', $datetime['date']);
    }

    public function testTicketResult(): void
    {
        $query  = $this->_em->createQuery('SELECT d.id, d.time, d.date, d.datetime FROM ' . DateTimeModel::class . ' d ORDER BY d.date ASC');
        $result = $query->getResult();

        $this->assertCount(2, $result);

        $this->assertInstanceOf('DateTime', $result[0]['time']);
        $this->assertInstanceOf('DateTime', $result[0]['date']);
        $this->assertInstanceOf('DateTime', $result[0]['datetime']);

        $this->assertEquals('2010-01-01 11:11:11', $result[0]['datetime']->format('Y-m-d G:i:s'));

        $this->assertInstanceOf('DateTime', $result[1]['time']);
        $this->assertInstanceOf('DateTime', $result[1]['date']);
        $this->assertInstanceOf('DateTime', $result[1]['datetime']);

        $this->assertEquals('2010-02-02 12:12:12', $result[1]['datetime']->format('Y-m-d G:i:s'));
    }

    public function loadFixtures(): void
    {
        $timezone = new DateTimeZone('America/Sao_Paulo');

        $dateTime1 = new DateTimeModel();
        $dateTime2 = new DateTimeModel();

        $dateTime1->date     = new DateTime('2010-01-01', $timezone);
        $dateTime1->time     = new DateTime('2010-01-01 11:11:11', $timezone);
        $dateTime1->datetime = new DateTime('2010-01-01 11:11:11', $timezone);

        $dateTime2->date     = new DateTime('2010-02-02', $timezone);
        $dateTime2->time     = new DateTime('2010-02-02 12:12:12', $timezone);
        $dateTime2->datetime = new DateTime('2010-02-02 12:12:12', $timezone);

        $this->_em->persist($dateTime1);
        $this->_em->persist($dateTime2);

        $this->_em->flush();
    }
}
