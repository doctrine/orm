<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1151
 */
class DDC1151Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function testQuoteForeignKey()
    {
        if ($this->_em->getConnection()->getDatabasePlatform()->getName() != 'postgresql') {
            $this->markTestSkipped("This test is useful for all databases, but designed only for postgresql.");
        }

        $sql = $this->_schemaTool->getCreateSchemaSql(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1151User'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1151Group'),
        ));

        $this->assertEquals("CREATE TABLE \"User\" (id INT NOT NULL, PRIMARY KEY(id))", $sql[0]);
        $this->assertEquals("CREATE TABLE ddc1151user_ddc1151group (ddc1151user_id INT NOT NULL, ddc1151group_id INT NOT NULL, PRIMARY KEY(ddc1151user_id, ddc1151group_id))", $sql[1]);
        $this->assertEquals("CREATE INDEX IDX_88A3259AC5AD08A ON ddc1151user_ddc1151group (ddc1151user_id)", $sql[2]);
        $this->assertEquals("CREATE INDEX IDX_88A32597357E0B1 ON ddc1151user_ddc1151group (ddc1151group_id)", $sql[3]);
        $this->assertEquals("CREATE TABLE \"Group\" (id INT NOT NULL, PRIMARY KEY(id))", $sql[4]);
        $this->assertEquals("CREATE SEQUENCE \"User_id_seq\" INCREMENT BY 1 MINVALUE 1 START 1", $sql[5]);
        $this->assertEquals("CREATE SEQUENCE \"Group_id_seq\" INCREMENT BY 1 MINVALUE 1 START 1", $sql[6]);
        $this->assertEquals("ALTER TABLE ddc1151user_ddc1151group ADD CONSTRAINT FK_88A3259AC5AD08A FOREIGN KEY (ddc1151user_id) REFERENCES \"User\" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE", $sql[7]);
        $this->assertEquals("ALTER TABLE ddc1151user_ddc1151group ADD CONSTRAINT FK_88A32597357E0B1 FOREIGN KEY (ddc1151group_id) REFERENCES \"Group\" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE", $sql[8]);
    }
}

/**
 * @Entity
 * @Table(name="`User`")
 */
class DDC1151User
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @ManyToMany(targetEntity="DDC1151Group") */
    public $groups;
}

/**
 * @Entity
 * @Table(name="`Group`")
 */
class DDC1151Group
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
}