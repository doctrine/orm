<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\SchemaTool;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\Tests\OrmFunctionalTestCase;

use function array_filter;
use function str_contains;

class DBAL483Test extends OrmFunctionalTestCase
{
    /** @group DBAL-483 */
    public function testDefaultValueIsComparedCorrectly(): void
    {
        $class = DBAL483Default::class;

        $this->createSchemaForModels($class);

        $updateSql = $this->getUpdateSchemaSqlForModels($class);

        $updateSql = array_filter($updateSql, static function ($sql) {
            return str_contains($sql, 'DBAL483');
        });

        self::assertCount(0, $updateSql);
    }
}

/** @Entity */
class DBAL483Default
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @var int
     * @Column(type="integer", options={"default": 0})
     */
    public $num;

    /**
     * @var string
     * @Column(type="string", options={"default": "foo"})
     */
    public $str = 'foo';
}
