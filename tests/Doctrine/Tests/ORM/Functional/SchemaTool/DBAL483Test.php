<?php

namespace Doctrine\Tests\ORM\Functional\SchemaTool;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Tools;
use Doctrine\Tests\OrmFunctionalTestCase;

class DBAL483Test extends OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->em->getConnection();

        $this->schemaTool = new Tools\SchemaTool($this->em);
    }

    /**
     * @group DBAL-483
     */
    public function testDefaultValueIsComparedCorrectly()
    {
        $class = $this->em->getClassMetadata(DBAL483Default::class);

        $this->schemaTool->createSchema([$class]);

        $updateSql = $this->schemaTool->getUpdateSchemaSql([$class]);

        $updateSql = array_filter($updateSql, function ($sql) {
            return strpos($sql, 'DBAL483') !== false;
        });

        self::assertEquals(0, count($updateSql));
    }
}

/**
 * @ORM\Entity
 */
class DBAL483Default
{
    /**
     * @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
     */
    public $id;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    public $num;

    /**
     * @ORM\Column(type="string", options={"default": "foo"})
     */
    public $str = "foo";
}
