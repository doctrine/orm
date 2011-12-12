<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Generic\BooleanModel;
use Doctrine\Tests\Models\Generic\DateTimeModel;
use Doctrine\Tests\Models\Generic\DecimalModel;
use Doctrine\Tests\Models\Generic\SerializationModel;

use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\DBAL\Types\Type as DBALType;

require_once __DIR__ . '/../../TestInit.php';

class TypeTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('generic');
        parent::setUp();
    }

    public function testDecimal()
    {
        $decimal = new DecimalModel();
        $decimal->decimal = 0.15;
        $decimal->highScale = 0.1515;

        $this->_em->persist($decimal);
        $this->_em->flush();
        $this->_em->clear();

        $dql = "SELECT d FROM Doctrine\Tests\Models\Generic\DecimalModel d";
        $decimal = $this->_em->createQuery($dql)->getSingleResult();

        $this->assertEquals(0.15, $decimal->decimal);
        $this->assertEquals(0.1515, $decimal->highScale);
    }

    public function testBoolean()
    {
        $bool = new BooleanModel();
        $bool->booleanField = true;

        $this->_em->persist($bool);
        $this->_em->flush();
        $this->_em->clear();

        $dql = "SELECT b FROM Doctrine\Tests\Models\Generic\BooleanModel b";
        $bool = $this->_em->createQuery($dql)->getSingleResult();

        $this->assertTrue($bool->booleanField);

        $bool->booleanField = false;

        $this->_em->flush();
        $this->_em->clear();

        $dql = "SELECT b FROM Doctrine\Tests\Models\Generic\BooleanModel b";
        $bool = $this->_em->createQuery($dql)->getSingleResult();

        $this->assertFalse($bool->booleanField);
    }

    public function testArray()
    {
        $serialize = new SerializationModel();
        $serialize->array["foo"] = "bar";
        $serialize->array["bar"] = "baz";

        $this->_em->persist($serialize);
        $this->_em->flush();
        $this->_em->clear();

        $dql = "SELECT s FROM Doctrine\Tests\Models\Generic\SerializationModel s";
        $serialize = $this->_em->createQuery($dql)->getSingleResult();

        $this->assertEquals(array("foo" => "bar", "bar" => "baz"), $serialize->array);
    }

    public function testObject()
    {
        $serialize = new SerializationModel();
        $serialize->object = new \stdClass();

        $this->_em->persist($serialize);
        $this->_em->flush();
        $this->_em->clear();

        $dql = "SELECT s FROM Doctrine\Tests\Models\Generic\SerializationModel s";
        $serialize = $this->_em->createQuery($dql)->getSingleResult();

        $this->assertInstanceOf('stdClass', $serialize->object);
    }

    public function testDate()
    {
        $dateTime = new DateTimeModel();
        $dateTime->date = new \DateTime('2009-10-01', new \DateTimeZone('Europe/Berlin'));

        $this->_em->persist($dateTime);
        $this->_em->flush();
        $this->_em->clear();

        $dateTimeDb = $this->_em->find('Doctrine\Tests\Models\Generic\DateTimeModel', $dateTime->id);

        $this->assertInstanceOf('DateTime', $dateTimeDb->date);
        $this->assertEquals('2009-10-01', $dateTimeDb->date->format('Y-m-d'));
    }

    public function testDateTime()
    {
        $dateTime = new DateTimeModel();
        $dateTime->datetime = new \DateTime('2009-10-02 20:10:52', new \DateTimeZone('Europe/Berlin'));

        $this->_em->persist($dateTime);
        $this->_em->flush();
        $this->_em->clear();

        $dateTimeDb = $this->_em->find('Doctrine\Tests\Models\Generic\DateTimeModel', $dateTime->id);

        $this->assertInstanceOf('DateTime', $dateTimeDb->datetime);
        $this->assertEquals('2009-10-02 20:10:52', $dateTimeDb->datetime->format('Y-m-d H:i:s'));

        $articles = $this->_em->getRepository( 'Doctrine\Tests\Models\Generic\DateTimeModel' )->findBy( array( 'datetime' => new \DateTime( "now" ) ) );
        $this->assertEquals( 0, count( $articles ) );
    }

    public function testDqlQueryBindDateTimeInstance()
    {
        $date = new \DateTime('2009-10-02 20:10:52', new \DateTimeZone('Europe/Berlin'));

        $dateTime = new DateTimeModel();
        $dateTime->datetime = $date;

        $this->_em->persist($dateTime);
        $this->_em->flush();
        $this->_em->clear();

        $dateTimeDb = $this->_em->createQuery('SELECT d FROM Doctrine\Tests\Models\Generic\DateTimeModel d WHERE d.datetime = ?1')
                                ->setParameter(1, $date, DBALType::DATETIME)
                                ->getSingleResult();
    }

    public function testDqlQueryBuilderBindDateTimeInstance()
    {
        $date = new \DateTime('2009-10-02 20:10:52', new \DateTimeZone('Europe/Berlin'));

        $dateTime = new DateTimeModel();
        $dateTime->datetime = $date;

        $this->_em->persist($dateTime);
        $this->_em->flush();
        $this->_em->clear();

        $dateTimeDb = $this->_em->createQueryBuilder()
                                 ->select('d')
                                 ->from('Doctrine\Tests\Models\Generic\DateTimeModel', 'd')
                                 ->where('d.datetime = ?1')
                                 ->setParameter(1, $date, DBALType::DATETIME)
                                 ->getQuery()->getSingleResult();
    }

    public function testTime()
    {
        $dateTime = new DateTimeModel();
        $dateTime->time = new \DateTime('2010-01-01 19:27:20');

        $this->_em->persist($dateTime);
        $this->_em->flush();
        $this->_em->clear();

        $dateTimeDb = $this->_em->find('Doctrine\Tests\Models\Generic\DateTimeModel', $dateTime->id);

        $this->assertInstanceOf('DateTime', $dateTime->time);
        $this->assertEquals('19:27:20', $dateTime->time->format('H:i:s'));
    }
}
