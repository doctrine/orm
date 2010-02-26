<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Generic\BooleanModel;
use Doctrine\Tests\Models\Generic\DateTimeModel;
use Doctrine\Tests\Models\Generic\DecimalModel;
use Doctrine\Tests\Models\Generic\SerializationModel;

use Doctrine\ORM\Mapping\AssociationMapping;

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

        $this->assertType('stdClass', $serialize->object);
    }
}