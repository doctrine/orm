<?php

namespace Doctrine\Tests\ORM\Functional\SchemaTool;

use Doctrine\ORM\Tools\SchemaTool,
    Doctrine\ORM\Mapping\ClassMetadata;

require_once __DIR__ . '/../../../TestInit.php';

class MySqlSchemaToolTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        parent::setUp();
        if ($this->_em->getConnection()->getDatabasePlatform()->getName() !== 'mysql') {
            $this->markTestSkipped('The ' . __CLASS__ .' requires the use of mysql.');
        }
    }

    public function testGetCreateSchemaSql()
    {
        $classes = array(
            $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsGroup'),
            $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsUser'),
            $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress'),
            $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsEmail'),
            $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsPhonenumber'),
        );

        $tool = new SchemaTool($this->_em);
        $sql = $tool->getCreateSchemaSql($classes);

        $this->assertEquals("CREATE TABLE cms_groups (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB", $sql[0]);
        $this->assertEquals("CREATE TABLE cms_users (id INT AUTO_INCREMENT NOT NULL, email_id INT DEFAULT NULL, status VARCHAR(50) DEFAULT NULL, username VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_3AF03EC5F85E0677 (username), UNIQUE INDEX UNIQ_3AF03EC5A832C1C9 (email_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB", $sql[1]);
        $this->assertEquals("CREATE TABLE cms_users_groups (user_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_7EA9409AA76ED395 (user_id), INDEX IDX_7EA9409AFE54D947 (group_id), PRIMARY KEY(user_id, group_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB", $sql[2]);
        $this->assertEquals("CREATE TABLE cms_addresses (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, country VARCHAR(50) NOT NULL, zip VARCHAR(50) NOT NULL, city VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_ACAC157BA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB", $sql[3]);
        $this->assertEquals("CREATE TABLE cms_emails (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(250) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB", $sql[4]);
        $this->assertEquals("CREATE TABLE cms_phonenumbers (phonenumber VARCHAR(50) NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_F21F790FA76ED395 (user_id), PRIMARY KEY(phonenumber)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB", $sql[5]);
        $this->assertEquals("ALTER TABLE cms_users ADD CONSTRAINT FK_3AF03EC5A832C1C9 FOREIGN KEY (email_id) REFERENCES cms_emails (id)", $sql[6]);
        $this->assertEquals("ALTER TABLE cms_users_groups ADD CONSTRAINT FK_7EA9409AA76ED395 FOREIGN KEY (user_id) REFERENCES cms_users (id)", $sql[7]);
        $this->assertEquals("ALTER TABLE cms_users_groups ADD CONSTRAINT FK_7EA9409AFE54D947 FOREIGN KEY (group_id) REFERENCES cms_groups (id)", $sql[8]);
        $this->assertEquals("ALTER TABLE cms_addresses ADD CONSTRAINT FK_ACAC157BA76ED395 FOREIGN KEY (user_id) REFERENCES cms_users (id)", $sql[9]);
        $this->assertEquals("ALTER TABLE cms_phonenumbers ADD CONSTRAINT FK_F21F790FA76ED395 FOREIGN KEY (user_id) REFERENCES cms_users (id)", $sql[10]);

        $this->assertEquals(11, count($sql));
    }

    public function testGetCreateSchemaSql2()
    {
        $classes = array(
            $this->_em->getClassMetadata('Doctrine\Tests\Models\Generic\DecimalModel')
        );

        $tool = new SchemaTool($this->_em);
        $sql = $tool->getCreateSchemaSql($classes);

        $this->assertEquals(1, count($sql));
        $this->assertEquals("CREATE TABLE decimal_model (id INT AUTO_INCREMENT NOT NULL, `decimal` NUMERIC(5, 2) NOT NULL, `high_scale` NUMERIC(14, 4) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB", $sql[0]);
    }

    public function testGetCreateSchemaSql3()
    {
        $classes = array(
            $this->_em->getClassMetadata('Doctrine\Tests\Models\Generic\BooleanModel')
        );

        $tool = new SchemaTool($this->_em);
        $sql = $tool->getCreateSchemaSql($classes);

        $this->assertEquals(1, count($sql));
        $this->assertEquals("CREATE TABLE boolean_model (id INT AUTO_INCREMENT NOT NULL, booleanField TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB", $sql[0]);
    }

    /**
     * @group DBAL-204
     */
    public function testGetCreateSchemaSql4()
    {
        $classes = array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\MysqlSchemaNamespacedEntity')
        );

        $tool = new SchemaTool($this->_em);
        $sql = $tool->getCreateSchemaSql($classes);

        $this->assertEquals(0, count($sql));
    }

}

/**
 * @Entity
 * @Table("namespace.entity")
 */
class MysqlSchemaNamespacedEntity
{
    /** @Column(type="integer") @Id @GeneratedValue */
    public $id;
}

