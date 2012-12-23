<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\UnitOfWork;

require_once __DIR__ . '/../../../TestInit.php';

class DDC2182Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function testPassColumnOptionsToJoinColumns()
    {
        if ($this->_em->getConnection()->getDatabasePlatform()->getName() != 'mysql') {
            $this->markTestSkipped("This test is useful for all databases, but designed only for mysql.");
        }

        $sql = $this->_schemaTool->getCreateSchemaSql(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2182OptionParent'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2182OptionChild'),
        ));

        $this->assertEquals("CREATE TABLE DDC2182OptionParent (id INT UNSIGNED NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB", $sql[0]);
        $this->assertEquals("CREATE TABLE DDC2182OptionChild (id VARCHAR(255) NOT NULL, parent_id INT UNSIGNED DEFAULT NULL, INDEX IDX_B314D4AD727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB", $sql[1]);
        $this->assertEquals("ALTER TABLE DDC2182OptionChild ADD CONSTRAINT FK_B314D4AD727ACA70 FOREIGN KEY (parent_id) REFERENCES DDC2182OptionParent (id)", $sql[2]);
    }
}

/**
 * @Entity
 * @Table
 */
class DDC2182OptionParent
{
    /** @Id @Column(type="integer", options={"unsigned": true}) */
    private $id;
}

/**
 * @Entity
 * @Table
 */
class DDC2182OptionChild
{
    /** @Id @Column */
    private $id;

    /**
     * @ManyToOne(targetEntity="DDC2182OptionParent")
     * @JoinColumn(referencedColumnName="id")
     */
    private $parent;
}
