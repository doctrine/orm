<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\Tests\Models\Generic\BooleanModel;
use Doctrine\Tests\Models\Generic\DateTimeModel;
use Doctrine\Tests\Models\Generic\DecimalModel;
use Doctrine\Tests\Models\Generic\SerializationModel;
use Doctrine\Tests\OrmFunctionalTestCase;
use stdClass;

class TypeTest extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        $this->useModelSet('generic');

        parent::setUp();
    }

    public function testDecimal() : void
    {
        $decimal            = new DecimalModel();
        $decimal->decimal   = 0.15;
        $decimal->highScale = 0.1515;

        $this->em->persist($decimal);
        $this->em->flush();
        $this->em->clear();

        $dql     = 'SELECT d FROM ' . DecimalModel::class . ' d';
        $decimal = $this->em->createQuery($dql)->getSingleResult();

        self::assertSame('0.15', $decimal->decimal);
        self::assertSame('0.1515', $decimal->highScale);
    }

    /**
     * @group DDC-1394
     */
    public function testBoolean() : void
    {
        $bool               = new BooleanModel();
        $bool->booleanField = true;

        $this->em->persist($bool);
        $this->em->flush();
        $this->em->clear();

        $dql  = 'SELECT b FROM ' . BooleanModel::class . ' b WHERE b.booleanField = true';
        $bool = $this->em->createQuery($dql)->getSingleResult();

        self::assertTrue($bool->booleanField);

        $bool->booleanField = false;

        $this->em->flush();
        $this->em->clear();

        $dql  = 'SELECT b FROM ' . BooleanModel::class . ' b WHERE b.booleanField = false';
        $bool = $this->em->createQuery($dql)->getSingleResult();

        self::assertFalse($bool->booleanField);
    }

    public function testArray() : void
    {
        $serialize               = new SerializationModel();
        $serialize->array['foo'] = 'bar';
        $serialize->array['bar'] = 'baz';

        $this->em->persist($serialize);
        $this->em->flush();
        $this->em->clear();

        $dql       = 'SELECT s FROM ' . SerializationModel::class . ' s';
        $serialize = $this->em->createQuery($dql)->getSingleResult();

        self::assertSame(['foo' => 'bar', 'bar' => 'baz'], $serialize->array);
    }

    public function testObject() : void
    {
        $serialize         = new SerializationModel();
        $serialize->object = new stdClass();

        $this->em->persist($serialize);
        $this->em->flush();
        $this->em->clear();

        $dql       = 'SELECT s FROM ' . SerializationModel::class . ' s';
        $serialize = $this->em->createQuery($dql)->getSingleResult();

        self::assertInstanceOf('stdClass', $serialize->object);
    }

    public function testDate() : void
    {
        $dateTime       = new DateTimeModel();
        $dateTime->date = new DateTime('2009-10-01', new DateTimeZone('Europe/Berlin'));

        $this->em->persist($dateTime);
        $this->em->flush();
        $this->em->clear();

        $dateTimeDb = $this->em->find(DateTimeModel::class, $dateTime->id);

        self::assertInstanceOf(DateTime::class, $dateTimeDb->date);
        self::assertEquals('2009-10-01', $dateTimeDb->date->format('Y-m-d'));
    }

    public function testDateTime() : void
    {
        $dateTime           = new DateTimeModel();
        $dateTime->datetime = new DateTime('2009-10-02 20:10:52', new DateTimeZone('Europe/Berlin'));

        $this->em->persist($dateTime);
        $this->em->flush();
        $this->em->clear();

        $dateTimeDb = $this->em->find(DateTimeModel::class, $dateTime->id);

        self::assertInstanceOf(DateTime::class, $dateTimeDb->datetime);
        self::assertEquals('2009-10-02 20:10:52', $dateTimeDb->datetime->format('Y-m-d H:i:s'));

        $articles = $this->em
            ->getRepository(DateTimeModel::class)
            ->findBy(['datetime' => new DateTime('now')]);

        self::assertEmpty($articles);
    }

    public function testDqlQueryBindDateTimeInstance() : void
    {
        $date = new DateTime('2009-10-02 20:10:52', new DateTimeZone('Europe/Berlin'));

        $dateTime           = new DateTimeModel();
        $dateTime->datetime = $date;

        $this->em->persist($dateTime);
        $this->em->flush();
        $this->em->clear();

        $dateTimeDb = $this->em
            ->createQuery('SELECT d FROM Doctrine\Tests\Models\Generic\DateTimeModel d WHERE d.datetime = ?1')
            ->setParameter(1, $date, DBALType::DATETIME)
            ->getSingleResult();

        self::assertInstanceOf(DateTime::class, $dateTimeDb->datetime);
        self::assertSame('2009-10-02 20:10:52', $dateTimeDb->datetime->format('Y-m-d H:i:s'));
    }

    public function testDqlQueryBuilderBindDateTimeInstance() : void
    {
        $date = new DateTime('2009-10-02 20:10:52', new DateTimeZone('Europe/Berlin'));

        $dateTime           = new DateTimeModel();
        $dateTime->datetime = $date;

        $this->em->persist($dateTime);
        $this->em->flush();
        $this->em->clear();

        $dateTimeDb = $this->em->createQueryBuilder()
             ->select('d')
             ->from(DateTimeModel::class, 'd')
             ->where('d.datetime = ?1')
             ->setParameter(1, $date, DBALType::DATETIME)
             ->getQuery()
             ->getSingleResult();

        self::assertInstanceOf(DateTime::class, $dateTimeDb->datetime);
        self::assertSame('2009-10-02 20:10:52', $dateTimeDb->datetime->format('Y-m-d H:i:s'));
    }

    public function testTime() : void
    {
        $dateTime       = new DateTimeModel();
        $dateTime->time = new DateTime('2010-01-01 19:27:20');

        $this->em->persist($dateTime);
        $this->em->flush();
        $this->em->clear();

        $dateTimeDb = $this->em->find(DateTimeModel::class, $dateTime->id);

        self::assertInstanceOf(DateTime::class, $dateTime->time);
        self::assertSame('19:27:20', $dateTime->time->format('H:i:s'));
    }
}
