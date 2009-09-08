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
    
    public function testGetCreateSchemaSql()
    {
        $classes = array(
            $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress'),
            $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsUser'),
            $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsPhonenumber'),
        );

        $tool = new SchemaTool($this->_em);
        $sql = $tool->getCreateSchemaSql($classes);
        $this->assertEquals(count($sql), 10);
        
        $this->assertEquals("CREATE TABLE cms_addresses (id INT NOT NULL, country VARCHAR(50) NOT NULL, zip VARCHAR(50) NOT NULL, city VARCHAR(50) NOT NULL, user_id INT DEFAULT NULL, PRIMARY KEY(id))", $sql[0]);
        $this->assertEquals("CREATE TABLE cms_users_groups (user_id INT DEFAULT NULL, group_id INT DEFAULT NULL, PRIMARY KEY(user_id, group_id))", $sql[1]);
        $this->assertEquals("CREATE TABLE cms_users (id INT NOT NULL, status VARCHAR(50) NOT NULL, username VARCHAR(255) NOT NULL UNIQUE, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))", $sql[2]);
        $this->assertEquals("CREATE TABLE cms_phonenumbers (phonenumber VARCHAR(50) NOT NULL, user_id INT DEFAULT NULL, PRIMARY KEY(phonenumber))", $sql[3]);
        $this->assertEquals("ALTER TABLE cms_addresses ADD FOREIGN KEY (user_id) REFERENCES cms_users(id) NOT DEFERRABLE INITIALLY IMMEDIATE", $sql[4]);
        $this->assertEquals("ALTER TABLE cms_users_groups ADD FOREIGN KEY (user_id) REFERENCES cms_users(id) NOT DEFERRABLE INITIALLY IMMEDIATE", $sql[5]);
        $this->assertEquals("ALTER TABLE cms_users_groups ADD FOREIGN KEY (group_id) REFERENCES cms_groups(id) NOT DEFERRABLE INITIALLY IMMEDIATE", $sql[6]);
        $this->assertEquals("ALTER TABLE cms_phonenumbers ADD FOREIGN KEY (user_id) REFERENCES cms_users(id) NOT DEFERRABLE INITIALLY IMMEDIATE", $sql[7]);
        $this->assertEquals("CREATE SEQUENCE cms_addresses_id_seq INCREMENT BY 20 START 1", $sql[8]);
        $this->assertEquals("CREATE SEQUENCE cms_users_id_seq INCREMENT BY 20 START 1", $sql[9]);
    }
    
    public function testGetCreateSchemaSql2()
    {
        $classes = array(
            $this->_em->getClassMetadata('Doctrine\Tests\Models\Generic\DecimalModel')
        );

        $tool = new SchemaTool($this->_em);
        $sql = $tool->getCreateSchemaSql($classes);

        $this->assertEquals(2, count($sql));
        
        $this->assertEquals("CREATE TABLE decimal_model (id INT NOT NULL, decimal NUMERIC(2, 5) NOT NULL, PRIMARY KEY(id))", $sql[0]);
        $this->assertEquals("CREATE SEQUENCE decimal_model_id_seq INCREMENT BY 20 START 1", $sql[1]);
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
        $this->assertEquals("CREATE SEQUENCE boolean_model_id_seq INCREMENT BY 20 START 1", $sql[1]);
    }
}
