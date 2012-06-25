<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-1360
 */
class DDC1360Test extends OrmFunctionalTestCase
{
    public function testSchemaDoubleQuotedCreate()
    {
        if ($this->_em->getConnection()->getDatabasePlatform()->getName() != "postgresql") {
            $this->markTestSkipped("PostgreSQL only test.");
        }

        $sql = $this->_schemaTool->getCreateSchemaSQL(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1360DoubleQuote')
        ));

        $this->assertEquals(array(
            'CREATE TABLE "user"."user" (id INT NOT NULL, PRIMARY KEY(id))',
            'CREATE SEQUENCE "user"."user_id_seq" INCREMENT BY 1 MINVALUE 1 START 1',
        ), $sql);
    }
}

/**
 * @Entity @Table(name="`user`.`user`")
 */
class DDC1360DoubleQuote
{
    /** @Id @GeneratedValue @Column(type="integer") */
    public $id;
}

