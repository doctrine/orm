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
    protected function setUp(): void
    {
        $this->useModelSet('generic');

        parent::setUp();
    }

    public function testDecimal(): void
    {
        $decimal            = new DecimalModel();
        $decimal->decimal   = 0.15;
        $decimal->highScale = 0.1515;

        $this->_em->persist($decimal);
        $this->_em->flush();
        $this->_em->clear();

        $dql     = 'SELECT d FROM ' . DecimalModel::class . ' d';
        $decimal = $this->_em->createQuery($dql)->getSingleResult();

        $this->assertSame('0.15', $decimal->decimal);
        $this->assertSame('0.1515', $decimal->highScale);
    }

    /**
     * @group DDC-1394
     */
    public function testBoolean(): void
    {
        $bool               = new BooleanModel();
        $bool->booleanField = true;

        $this->_em->persist($bool);
        $this->_em->flush();
        $this->_em->clear();

        $dql  = 'SELECT b FROM ' . BooleanModel::class . ' b WHERE b.booleanField = true';
        $bool = $this->_em->createQuery($dql)->getSingleResult();

        $this->assertTrue($bool->booleanField);

        $bool->booleanField = false;

        $this->_em->flush();
        $this->_em->clear();

        $dql  = 'SELECT b FROM ' . BooleanModel::class . ' b WHERE b.booleanField = false';
        $bool = $this->_em->createQuery($dql)->getSingleResult();

        $this->assertFalse($bool->booleanField);
    }

    public function testArray(): void
    {
        $serialize               = new SerializationModel();
        $serialize->array['foo'] = 'bar';
        $serialize->array['bar'] = 'baz';

        $this->_em->persist($serialize);
        $this->_em->flush();
        $this->_em->clear();

        $dql       = 'SELECT s FROM ' . SerializationModel::class . ' s';
        $serialize = $this->_em->createQuery($dql)->getSingleResult();

        $this->assertSame(['foo' => 'bar', 'bar' => 'baz'], $serialize->array);
    }

    public function testObject(): void
    {
        $serialize         = new SerializationModel();
        $serialize->object = new stdClass();

        $this->_em->persist($serialize);
        $this->_em->flush();
        $this->_em->clear();

        $dql       = 'SELECT s FROM ' . SerializationModel::class . ' s';
        $serialize = $this->_em->createQuery($dql)->getSingleResult();

        $this->assertInstanceOf('stdClass', $serialize->object);
    }

    public function testDate(): void
    {
        $dateTime       = new DateTimeModel();
        $dateTime->date = new DateTime('2009-10-01', new DateTimeZone('Europe/Berlin'));

        $this->_em->persist($dateTime);
        $this->_em->flush();
        $this->_em->clear();

        $dateTimeDb = $this->_em->find(DateTimeModel::class, $dateTime->id);

        $this->assertInstanceOf(DateTime::class, $dateTimeDb->date);
        $this->assertSame('2009-10-01', $dateTimeDb->date->format('Y-m-d'));
    }

    public function testDateTime(): void
    {
        $dateTime           = new DateTimeModel();
        $dateTime->datetime = new DateTime('2009-10-02 20:10:52', new DateTimeZone('Europe/Berlin'));

        $this->_em->persist($dateTime);
        $this->_em->flush();
        $this->_em->clear();

        $dateTimeDb = $this->_em->find(DateTimeModel::class, $dateTime->id);

        $this->assertInstanceOf(DateTime::class, $dateTimeDb->datetime);
        $this->assertSame('2009-10-02 20:10:52', $dateTimeDb->datetime->format('Y-m-d H:i:s'));

        $articles = $this->_em->getRepository(DateTimeModel::class)
                              ->findBy(['datetime' => new DateTime()]);

        $this->assertEmpty($articles);
    }

    public function testDqlQueryBindDateTimeInstance(): void
    {
        $date = new DateTime('2009-10-02 20:10:52', new DateTimeZone('Europe/Berlin'));

        $dateTime           = new DateTimeModel();
        $dateTime->datetime = $date;

        $this->_em->persist($dateTime);
        $this->_em->flush();
        $this->_em->clear();

        $dateTimeDb = $this->_em->createQuery('SELECT d FROM Doctrine\Tests\Models\Generic\DateTimeModel d WHERE d.datetime = ?1')
                                ->setParameter(1, $date, DBALType::DATETIME)
                                ->getSingleResult();

        $this->assertInstanceOf(DateTime::class, $dateTimeDb->datetime);
        $this->assertSame('2009-10-02 20:10:52', $dateTimeDb->datetime->format('Y-m-d H:i:s'));
    }

    public function testDqlQueryBuilderBindDateTimeInstance(): void
    {
        $date = new DateTime('2009-10-02 20:10:52', new DateTimeZone('Europe/Berlin'));

        $dateTime           = new DateTimeModel();
        $dateTime->datetime = $date;

        $this->_em->persist($dateTime);
        $this->_em->flush();
        $this->_em->clear();

        $dateTimeDb = $this->_em->createQueryBuilder()
                                 ->select('d')
                                 ->from(DateTimeModel::class, 'd')
                                 ->where('d.datetime = ?1')
                                 ->setParameter(1, $date, DBALType::DATETIME)
                                 ->getQuery()->getSingleResult();

        $this->assertInstanceOf(DateTime::class, $dateTimeDb->datetime);
        $this->assertSame('2009-10-02 20:10:52', $dateTimeDb->datetime->format('Y-m-d H:i:s'));
    }

    public function testTime(): void
    {
        $dateTime       = new DateTimeModel();
        $dateTime->time = new DateTime('2010-01-01 19:27:20');

        $this->_em->persist($dateTime);
        $this->_em->flush();
        $this->_em->clear();

        $dateTimeDb = $this->_em->find(DateTimeModel::class, $dateTime->id);

        $this->assertInstanceOf(DateTime::class, $dateTimeDb->time);
        $this->assertSame('19:27:20', $dateTimeDb->time->format('H:i:s'));
    }
}
