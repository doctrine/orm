<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use DateTime;
use DateTimeZone;
use Doctrine\Tests\Models\Generic\DateTimeModel;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('DDC-657')]
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

        self::assertInstanceOf(DateTimeModel::class, $datetime);

        self::assertInstanceOf(DateTime::class, $datetime->datetime);
        self::assertInstanceOf(DateTime::class, $datetime->time);
        self::assertInstanceOf(DateTime::class, $datetime->date);
    }

    public function testScalarResult(): void
    {
        $query  = $this->_em->createQuery('SELECT d.id, d.time, d.date, d.datetime FROM ' . DateTimeModel::class . ' d ORDER BY d.date ASC');
        $result = $query->getScalarResult();

        self::assertCount(2, $result);

        self::assertStringContainsString('11:11:11', $result[0]['time']);
        self::assertStringContainsString('2010-01-01', $result[0]['date']);
        self::assertStringContainsString('2010-01-01 11:11:11', $result[0]['datetime']);

        self::assertStringContainsString('12:12:12', $result[1]['time']);
        self::assertStringContainsString('2010-02-02', $result[1]['date']);
        self::assertStringContainsString('2010-02-02 12:12:12', $result[1]['datetime']);
    }

    public function testaTicketEntityArrayResult(): void
    {
        $query  = $this->_em->createQuery('SELECT d FROM ' . DateTimeModel::class . ' d ORDER BY d.date ASC');
        $result = $query->getArrayResult();

        self::assertCount(2, $result);

        self::assertInstanceOf(DateTime::class, $result[0]['datetime']);
        self::assertInstanceOf(DateTime::class, $result[0]['time']);
        self::assertInstanceOf(DateTime::class, $result[0]['date']);

        self::assertInstanceOf(DateTime::class, $result[1]['datetime']);
        self::assertInstanceOf(DateTime::class, $result[1]['time']);
        self::assertInstanceOf(DateTime::class, $result[1]['date']);
    }

    public function testTicketSingleResult(): void
    {
        $query    = $this->_em->createQuery('SELECT d.id, d.time, d.date, d.datetime FROM ' . DateTimeModel::class . ' d ORDER BY d.date ASC');
        $datetime = $query->setMaxResults(1)->getSingleResult();

        self::assertIsArray($datetime);

        self::assertInstanceOf(DateTime::class, $datetime['datetime']);
        self::assertInstanceOf(DateTime::class, $datetime['time']);
        self::assertInstanceOf(DateTime::class, $datetime['date']);
    }

    public function testTicketResult(): void
    {
        $query  = $this->_em->createQuery('SELECT d.id, d.time, d.date, d.datetime FROM ' . DateTimeModel::class . ' d ORDER BY d.date ASC');
        $result = $query->getResult();

        self::assertCount(2, $result);

        self::assertInstanceOf(DateTime::class, $result[0]['time']);
        self::assertInstanceOf(DateTime::class, $result[0]['date']);
        self::assertInstanceOf(DateTime::class, $result[0]['datetime']);

        self::assertEquals('2010-01-01 11:11:11', $result[0]['datetime']->format('Y-m-d G:i:s'));

        self::assertInstanceOf(DateTime::class, $result[1]['time']);
        self::assertInstanceOf(DateTime::class, $result[1]['date']);
        self::assertInstanceOf(DateTime::class, $result[1]['datetime']);

        self::assertEquals('2010-02-02 12:12:12', $result[1]['datetime']->format('Y-m-d G:i:s'));
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
