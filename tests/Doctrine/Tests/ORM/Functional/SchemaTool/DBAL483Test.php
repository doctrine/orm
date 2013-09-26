<?php

namespace Doctrine\Tests\ORM\Functional\SchemaTool;

use Doctrine\ORM\Tools;

class DBAL483Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        $conn = $this->_em->getConnection();

        $this->schemaTool = new Tools\SchemaTool($this->_em);
    }

    /**
     * @group DBAL-483
     */
    public function testDefaultValueIsComparedCorrectly()
    {
        $class = $this->_em->getClassMetadata(__NAMESPACE__ . '\\DBAL483Default');

        $this->schemaTool->createSchema(array($class));

        $updateSql = $this->schemaTool->getUpdateSchemaSql(array($class));

        $updateSql = array_filter($updateSql, function ($sql) {
            return strpos($sql, 'DBAL483') !== false;
        });

        $this->assertEquals(0, count($updateSql));
    }
}

/**
 * @Entity
 */
class DBAL483Default
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     */
    public $id;

    /**
     * @Column(type="integer", options={"default": 0})
     */
    public $num;

    /**
     * @Column(type="string", options={"default": "foo"})
     */
    public $str = "foo";
}
