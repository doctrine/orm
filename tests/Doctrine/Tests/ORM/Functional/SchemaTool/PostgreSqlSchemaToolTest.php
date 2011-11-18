<?php

namespace Doctrine\Tests\ORM\Functional\SchemaTool;

use Doctrine\ORM\Tools\SchemaTool,
    Doctrine\ORM\Mapping\ClassMetadata;

require_once __DIR__ . '/../../../TestInit.php';

class PostgreSqlSchemaToolTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        parent::setUp();
        if ($this->_em->getConnection()->getDatabasePlatform()->getName() !== 'postgresql') {
            $this->markTestSkipped('The ' . __CLASS__ .' requires the use of postgresql.');
        }
    }

    public function testPostgresMetadataSequenceIncrementedBy10()
    {
        $address = $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress');
        $this->assertEquals(1, $address->sequenceGeneratorDefinition['allocationSize']);
    }

    public function testGetCreateSchemaSql()
    {
        $classes = array(
            $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress'),
            $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsUser'),
            $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsPhonenumber'),
        );

        $tool = new SchemaTool($this->_em);
        $sql = $tool->getCreateSchemaSql($classes);

        $this->assertEquals("CREATE TABLE cms_addresses (id INT NOT NULL, user_id INT DEFAULT NULL, country VARCHAR(50) NOT NULL, zip VARCHAR(50) NOT NULL, city VARCHAR(50) NOT NULL, PRIMARY KEY(id))", $sql[0]);
        $this->assertEquals("CREATE UNIQUE INDEX UNIQ_ACAC157BA76ED395 ON cms_addresses (user_id)", $sql[1]);
        $this->assertEquals("CREATE TABLE cms_users (id INT NOT NULL, status VARCHAR(50) NOT NULL, username VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))", $sql[2]);
        $this->assertEquals("CREATE UNIQUE INDEX UNIQ_3AF03EC5F85E0677 ON cms_users (username)", $sql[3]);
        $this->assertEquals("CREATE TABLE cms_users_groups (user_id INT NOT NULL, group_id INT NOT NULL, PRIMARY KEY(user_id, group_id))", $sql[4]);
        $this->assertEquals("CREATE INDEX IDX_7EA9409AA76ED395 ON cms_users_groups (user_id)", $sql[5]);
        $this->assertEquals("CREATE INDEX IDX_7EA9409AFE54D947 ON cms_users_groups (group_id)", $sql[6]);
        $this->assertEquals("CREATE TABLE cms_phonenumbers (phonenumber VARCHAR(50) NOT NULL, user_id INT DEFAULT NULL, PRIMARY KEY(phonenumber))", $sql[7]);
        $this->assertEquals("CREATE INDEX IDX_F21F790FA76ED395 ON cms_phonenumbers (user_id)", $sql[8]);
        $this->assertEquals("CREATE SEQUENCE cms_addresses_id_seq INCREMENT BY 1 MINVALUE 1 START 1", $sql[9]);
        $this->assertEquals("CREATE SEQUENCE cms_users_id_seq INCREMENT BY 1 MINVALUE 1 START 1", $sql[10]);
        $this->assertEquals("ALTER TABLE cms_addresses ADD CONSTRAINT FK_ACAC157BA76ED395 FOREIGN KEY (user_id) REFERENCES cms_users(id) NOT DEFERRABLE INITIALLY IMMEDIATE", $sql[11]);
        $this->assertEquals("ALTER TABLE cms_users_groups ADD CONSTRAINT FK_7EA9409AA76ED395 FOREIGN KEY (user_id) REFERENCES cms_users(id) NOT DEFERRABLE INITIALLY IMMEDIATE", $sql[12]);
        $this->assertEquals("ALTER TABLE cms_users_groups ADD CONSTRAINT FK_7EA9409AFE54D947 FOREIGN KEY (group_id) REFERENCES cms_groups(id) NOT DEFERRABLE INITIALLY IMMEDIATE", $sql[13]);
        $this->assertEquals("ALTER TABLE cms_phonenumbers ADD CONSTRAINT FK_F21F790FA76ED395 FOREIGN KEY (user_id) REFERENCES cms_users(id) NOT DEFERRABLE INITIALLY IMMEDIATE", $sql[14]);

        $this->assertEquals(count($sql), 15);
    }

    public function testGetCreateSchemaSql2()
    {
        $classes = array(
            $this->_em->getClassMetadata('Doctrine\Tests\Models\Generic\DecimalModel')
        );

        $tool = new SchemaTool($this->_em);
        $sql = $tool->getCreateSchemaSql($classes);

        $this->assertEquals(2, count($sql));

        $this->assertEquals('CREATE TABLE decimal_model (id INT NOT NULL, "decimal" NUMERIC(5, 2) NOT NULL, "high_scale" NUMERIC(14, 4) NOT NULL, PRIMARY KEY(id))', $sql[0]);
        $this->assertEquals("CREATE SEQUENCE decimal_model_id_seq INCREMENT BY 1 MINVALUE 1 START 1", $sql[1]);
    }

    public function testGetCreateSchemaSql3()
    {
        $classes = array(
            $this->_em->getClassMetadata('Doctrine\Tests\Models\Generic\BooleanModel')
        );

        $tool = new SchemaTool($this->_em);
        $sql = $tool->getCreateSchemaSql($classes);

        $this->assertEquals(2, count($sql));
        $this->assertEquals("CREATE TABLE boolean_model (id INT NOT NULL, booleanField BOOLEAN NOT NULL, PRIMARY KEY(id))", $sql[0]);
        $this->assertEquals("CREATE SEQUENCE boolean_model_id_seq INCREMENT BY 1 MINVALUE 1 START 1", $sql[1]);
    }

    public function testGetDropSchemaSql()
    {
        $classes = array(
            $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress'),
            $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsUser'),
            $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsPhonenumber'),
        );

        $tool = new SchemaTool($this->_em);
        try {
            $tool->createSchema($classes);
        } catch(\Exception $e) {

        }
        $sql = $tool->getDropSchemaSQL($classes);

        $this->assertEquals(13, count($sql));
        $dropSequenceSQLs = 0;
        foreach ($sql AS $stmt) {
            if (strpos($stmt, "DROP SEQUENCE") === 0) {
                $dropSequenceSQLs++;
            }
        }
        $this->assertEquals(4, $dropSequenceSQLs, "Expect 4 sequences to be dropped.");
    }
}
