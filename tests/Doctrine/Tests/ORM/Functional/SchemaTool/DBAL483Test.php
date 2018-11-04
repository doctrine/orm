<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\SchemaTool;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Tools;
use Doctrine\Tests\OrmFunctionalTestCase;
use function array_filter;
use function strpos;

class DBAL483Test extends OrmFunctionalTestCase
{
    public function setUp() : void
    {
        parent::setUp();

        $this->em->getConnection();

        $this->schemaTool = new Tools\SchemaTool($this->em);
    }

    /**
     * @group DBAL-483
     */
    public function testDefaultValueIsComparedCorrectly() : void
    {
        $class = $this->em->getClassMetadata(DBAL483Default::class);

        $this->schemaTool->createSchema([$class]);

        $updateSql = $this->schemaTool->getUpdateSchemaSql([$class]);

        $updateSql = array_filter($updateSql, static function ($sql) {
            return strpos($sql, 'DBAL483') !== false;
        });

        self::assertCount(0, $updateSql);
    }
}

/**
 * @ORM\Entity
 */
class DBAL483Default
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;

    /** @ORM\Column(type="integer", options={"default": 0}) */
    public $num;

    /** @ORM\Column(type="string", options={"default": "foo"}) */
    public $str = 'foo';
}
