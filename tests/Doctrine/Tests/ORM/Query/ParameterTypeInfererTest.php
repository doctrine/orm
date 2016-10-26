<?php

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\Query\ParameterTypeInferer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\Tests\OrmTestCase;
use PDO;

class ParameterTypeInfererTest extends OrmTestCase
{

    public function providerParameterTypeInferer()
    {
         $data = array(
            array(1,                 Type::INTEGER),
            array("bar",             PDO::PARAM_STR),
            array("1",               PDO::PARAM_STR),
            array(new \DateTime,     Type::DATETIME),
            array(array(2),          Connection::PARAM_INT_ARRAY),
            array(array("foo"),      Connection::PARAM_STR_ARRAY),
            array(array("1","2"),    Connection::PARAM_STR_ARRAY),
            array(array(),           Connection::PARAM_STR_ARRAY),
            array(true,              Type::BOOLEAN),
        );

        if (PHP_VERSION_ID >= 50500) {
            $data[] = array(new \DateTimeImmutable(), Type::DATETIME);
        }

        return $data;
    }

    /**
     * @dataProvider providerParameterTypeInferer
     */

    public function testParameterTypeInferer($value, $expected)
    {
        $this->assertEquals($expected, ParameterTypeInferer::inferType($value));
    }
}
