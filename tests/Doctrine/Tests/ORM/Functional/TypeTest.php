<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Types\ArrayType;
use Doctrine\DBAL\Types\ObjectType;
use Doctrine\DBAL\Types\Types;
use Doctrine\Tests\Models\Generic\BooleanModel;
use Doctrine\Tests\Models\Generic\DateTimeModel;
use Doctrine\Tests\Models\Generic\DecimalModel;
use Doctrine\Tests\Models\Generic\SerializationModel;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;
use stdClass;

use function class_exists;

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

        self::assertSame('0.15', $decimal->decimal);
        self::assertSame('0.1515', $decimal->highScale);
    }

    #[Group('DDC-1394')]
    public function testBoolean(): void
    {
        $bool               = new BooleanModel();
        $bool->booleanField = true;

        $this->_em->persist($bool);
        $this->_em->flush();
        $this->_em->clear();

        $dql  = 'SELECT b FROM ' . BooleanModel::class . ' b WHERE b.booleanField = true';
        $bool = $this->_em->createQuery($dql)->getSingleResult();

        self::assertTrue($bool->booleanField);

        $bool->booleanField = false;

        $this->_em->flush();
        $this->_em->clear();

        $dql  = 'SELECT b FROM ' . BooleanModel::class . ' b WHERE b.booleanField = false';
        $bool = $this->_em->createQuery($dql)->getSingleResult();

        self::assertFalse($bool->booleanField);
    }

    public function testArray(): void
    {
        if (! class_exists(ArrayType::class)) {
            self::markTestSkipped('Test valid for doctrine/dbal:3.x only.');
        }

        $serialize               = new SerializationModel();
        $serialize->array['foo'] = 'bar';
        $serialize->array['bar'] = 'baz';

        $this->createSchemaForModels(SerializationModel::class);
        static::$sharedConn->executeStatement('DELETE FROM serialize_model');
        $this->_em->persist($serialize);
        $this->_em->flush();
        $this->_em->clear();

        $dql       = 'SELECT s FROM ' . SerializationModel::class . ' s';
        $serialize = $this->_em->createQuery($dql)->getSingleResult();

        self::assertSame(['foo' => 'bar', 'bar' => 'baz'], $serialize->array);
    }

    public function testObject(): void
    {
        if (! class_exists(ObjectType::class)) {
            self::markTestSkipped('Test valid for doctrine/dbal:3.x only.');
        }

        $serialize         = new SerializationModel();
        $serialize->object = new stdClass();

        $this->createSchemaForModels(SerializationModel::class);
        static::$sharedConn->executeStatement('DELETE FROM serialize_model');
        $this->_em->persist($serialize);
        $this->_em->flush();
        $this->_em->clear();

        $dql       = 'SELECT s FROM ' . SerializationModel::class . ' s';
        $serialize = $this->_em->createQuery($dql)->getSingleResult();

        self::assertInstanceOf(stdClass::class, $serialize->object);
    }

    public function testDate(): void
    {
        $dateTime       = new DateTimeModel();
        $dateTime->date = new DateTime('2009-10-01', new DateTimeZone('Europe/Berlin'));

        $this->_em->persist($dateTime);
        $this->_em->flush();
        $this->_em->clear();

        $dateTimeDb = $this->_em->find(DateTimeModel::class, $dateTime->id);

        self::assertInstanceOf(DateTime::class, $dateTimeDb->date);
        self::assertSame('2009-10-01', $dateTimeDb->date->format('Y-m-d'));
    }

    public function testDateTime(): void
    {
        $dateTime           = new DateTimeModel();
        $dateTime->datetime = new DateTime('2009-10-02 20:10:52', new DateTimeZone('Europe/Berlin'));

        $this->_em->persist($dateTime);
        $this->_em->flush();
        $this->_em->clear();

        $dateTimeDb = $this->_em->find(DateTimeModel::class, $dateTime->id);

        self::assertInstanceOf(DateTime::class, $dateTimeDb->datetime);
        self::assertSame('2009-10-02 20:10:52', $dateTimeDb->datetime->format('Y-m-d H:i:s'));

        $articles = $this->_em->getRepository(DateTimeModel::class)
                              ->findBy(['datetime' => new DateTime()]);

        self::assertEmpty($articles);
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
                                ->setParameter(1, $date, Types::DATETIME_MUTABLE)
                                ->getSingleResult();

        self::assertInstanceOf(DateTime::class, $dateTimeDb->datetime);
        self::assertSame('2009-10-02 20:10:52', $dateTimeDb->datetime->format('Y-m-d H:i:s'));
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
                                 ->setParameter(1, $date, Types::DATETIME_MUTABLE)
                                 ->getQuery()->getSingleResult();

        self::assertInstanceOf(DateTime::class, $dateTimeDb->datetime);
        self::assertSame('2009-10-02 20:10:52', $dateTimeDb->datetime->format('Y-m-d H:i:s'));
    }

    public function testTime(): void
    {
        $dateTime       = new DateTimeModel();
        $dateTime->time = new DateTime('2010-01-01 19:27:20');

        $this->_em->persist($dateTime);
        $this->_em->flush();
        $this->_em->clear();

        $dateTimeDb = $this->_em->find(DateTimeModel::class, $dateTime->id);

        self::assertInstanceOf(DateTime::class, $dateTimeDb->time);
        self::assertSame('19:27:20', $dateTimeDb->time->format('H:i:s'));
    }
}
