<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group GH7841
 */
class GH7841Test extends OrmFunctionalTestCase
{
    public function testForeignKeysNotCompare() : void
    {
        if ($this->_em->getConnection()->getDatabasePlatform()->supportsForeignKeyConstraints()) {
            $this->markTestSkipped('Test for platforms without foreign keys support');
        }
        $class = $this->_em->getClassMetadata(GH7841Child::class);
        $this->_schemaTool->updateSchema([$class], true);
        $diff = $this->_schemaTool->getUpdateSchemaSql([$class], true);

        self::assertEmpty($diff);

        $this->_schemaTool->dropSchema([$class]);
    }
}

/**
 * @Entity
 */
class GH7841Parent
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @OneToMany(targetEntity=GH7841Child::class, mappedBy="parent") */
    public $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }
}

/**
 * @Entity
 */
class GH7841Child
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @ManyToOne(targetEntity=GH7841Parent::class) */
    public $parent;
}
