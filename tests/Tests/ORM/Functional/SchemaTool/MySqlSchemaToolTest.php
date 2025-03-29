<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\SchemaTool;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Visitor\Visitor;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsEmail;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsTag;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\Generic\BooleanModel;
use Doctrine\Tests\Models\Generic\DecimalModel;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

use function class_exists;

class MySqlSchemaToolTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->_em->getConnection()->getDatabasePlatform() instanceof MySQLPlatform) {
            self::markTestSkipped('The ' . self::class . ' requires the use of mysql.');
        }
    }

    public function testGetCreateSchemaSql(): void
    {
        $classes = [
            $this->_em->getClassMetadata(CmsGroup::class),
            $this->_em->getClassMetadata(CmsUser::class),
            $this->_em->getClassMetadata(CmsTag::class),
            $this->_em->getClassMetadata(CmsAddress::class),
            $this->_em->getClassMetadata(CmsEmail::class),
            $this->_em->getClassMetadata(CmsPhonenumber::class),
        ];

        $tool = new SchemaTool($this->_em);
        $sql  = $tool->getCreateSchemaSql($classes);

        self::assertThat($sql[0], self::logicalOr(
            // DBAL < 4.3
            self::equalTo('CREATE TABLE cms_groups (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'),
            // DBAL 4.3 (see https://github.com/doctrine/dbal/pull/6864)
            self::equalTo('CREATE TABLE cms_groups (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'),
        ));
        self::assertThat($sql[1], self::logicalOr(
            // DBAL 3
            self::equalTo('CREATE TABLE cms_users (id INT AUTO_INCREMENT NOT NULL, email_id INT DEFAULT NULL, status VARCHAR(50) DEFAULT NULL, username VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_3AF03EC5F85E0677 (username), UNIQUE INDEX UNIQ_3AF03EC5A832C1C9 (email_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'),
            // DBAL 4 (see https://github.com/doctrine/dbal/pull/4777)
            self::equalTo('CREATE TABLE cms_users (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(50) DEFAULT NULL, username VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, email_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_3AF03EC5F85E0677 (username), UNIQUE INDEX UNIQ_3AF03EC5A832C1C9 (email_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'),
            // DBAL 4.3 (see https://github.com/doctrine/dbal/pull/6864)
            self::equalTo('CREATE TABLE cms_users (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(50) DEFAULT NULL, username VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, email_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_3AF03EC5F85E0677 (username), UNIQUE INDEX UNIQ_3AF03EC5A832C1C9 (email_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'),
        ));
        self::assertThat($sql[2], self::logicalOr(
            // DBAL < 4.3
            self::equalTo('CREATE TABLE cms_users_groups (user_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_7EA9409AA76ED395 (user_id), INDEX IDX_7EA9409AFE54D947 (group_id), PRIMARY KEY(user_id, group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'),
            // DBAL 4.3 (see https://github.com/doctrine/dbal/pull/6864)
            self::equalTo('CREATE TABLE cms_users_groups (user_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_7EA9409AA76ED395 (user_id), INDEX IDX_7EA9409AFE54D947 (group_id), PRIMARY KEY (user_id, group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'),
        ));
        self::assertThat($sql[3], self::logicalOr(
            // DBAL < 4.3
            self::equalTo('CREATE TABLE cms_users_tags (user_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_93F5A1ADA76ED395 (user_id), INDEX IDX_93F5A1ADBAD26311 (tag_id), PRIMARY KEY(user_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'),
            // DBAL 4.3 (see https://github.com/doctrine/dbal/pull/6864)
            self::equalTo('CREATE TABLE cms_users_tags (user_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_93F5A1ADA76ED395 (user_id), INDEX IDX_93F5A1ADBAD26311 (tag_id), PRIMARY KEY (user_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'),
        ));
        self::assertThat($sql[4], self::logicalOr(
            // DBAL < 4.3
            self::equalTo('CREATE TABLE cms_tags (id INT AUTO_INCREMENT NOT NULL, tag_name VARCHAR(50) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'),
            // DBAL 4.3 (see https://github.com/doctrine/dbal/pull/6864)
            self::equalTo('CREATE TABLE cms_tags (id INT AUTO_INCREMENT NOT NULL, tag_name VARCHAR(50) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'),
        ));
        self::assertThat($sql[5], self::logicalOr(
            // DBAL 3
            self::equalTo('CREATE TABLE cms_addresses (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, country VARCHAR(50) NOT NULL, zip VARCHAR(50) NOT NULL, city VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_ACAC157BA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'),
            // DBAL 4 (see https://github.com/doctrine/dbal/pull/4777)
            self::equalTo('CREATE TABLE cms_addresses (id INT AUTO_INCREMENT NOT NULL, country VARCHAR(50) NOT NULL, zip VARCHAR(50) NOT NULL, city VARCHAR(50) NOT NULL, user_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_ACAC157BA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'),
            // DBAL 4.3 (see https://github.com/doctrine/dbal/pull/6864)
            self::equalTo('CREATE TABLE cms_addresses (id INT AUTO_INCREMENT NOT NULL, country VARCHAR(50) NOT NULL, zip VARCHAR(50) NOT NULL, city VARCHAR(50) NOT NULL, user_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_ACAC157BA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'),
        ));
        self::assertThat($sql[6], self::logicalOr(
            // DBAL < 4.3
            self::equalTo('CREATE TABLE cms_emails (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(250) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'),
            // DBAL 4.3 (see https://github.com/doctrine/dbal/pull/6864)
            self::equalTo('CREATE TABLE cms_emails (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(250) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'),
        ));
        self::assertThat($sql[7], self::logicalOr(
            // DBAL < 4.3
            self::equalTo('CREATE TABLE cms_phonenumbers (phonenumber VARCHAR(50) NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_F21F790FA76ED395 (user_id), PRIMARY KEY(phonenumber)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'),
            // DBAL 4.3 (see https://github.com/doctrine/dbal/pull/6864)
            self::equalTo('CREATE TABLE cms_phonenumbers (phonenumber VARCHAR(50) NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_F21F790FA76ED395 (user_id), PRIMARY KEY (phonenumber)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'),
        ));
        self::assertEquals('ALTER TABLE cms_users ADD CONSTRAINT FK_3AF03EC5A832C1C9 FOREIGN KEY (email_id) REFERENCES cms_emails (id)', $sql[8]);
        self::assertEquals('ALTER TABLE cms_users_groups ADD CONSTRAINT FK_7EA9409AA76ED395 FOREIGN KEY (user_id) REFERENCES cms_users (id)', $sql[9]);
        self::assertEquals('ALTER TABLE cms_users_groups ADD CONSTRAINT FK_7EA9409AFE54D947 FOREIGN KEY (group_id) REFERENCES cms_groups (id)', $sql[10]);
        self::assertEquals('ALTER TABLE cms_users_tags ADD CONSTRAINT FK_93F5A1ADA76ED395 FOREIGN KEY (user_id) REFERENCES cms_users (id)', $sql[11]);
        self::assertEquals('ALTER TABLE cms_users_tags ADD CONSTRAINT FK_93F5A1ADBAD26311 FOREIGN KEY (tag_id) REFERENCES cms_tags (id)', $sql[12]);
        self::assertEquals('ALTER TABLE cms_addresses ADD CONSTRAINT FK_ACAC157BA76ED395 FOREIGN KEY (user_id) REFERENCES cms_users (id)', $sql[13]);
        self::assertEquals('ALTER TABLE cms_phonenumbers ADD CONSTRAINT FK_F21F790FA76ED395 FOREIGN KEY (user_id) REFERENCES cms_users (id)', $sql[14]);

        self::assertCount(15, $sql);
    }

    public function testGetCreateSchemaSql2(): void
    {
        $classes = [$this->_em->getClassMetadata(DecimalModel::class)];

        $tool = new SchemaTool($this->_em);
        $sql  = $tool->getCreateSchemaSql($classes);

        self::assertCount(1, $sql);
        self::assertThat($sql[0], self::logicalOr(
            // DBAL < 4.3
            self::equalTo('CREATE TABLE decimal_model (id INT AUTO_INCREMENT NOT NULL, `decimal` NUMERIC(5, 2) NOT NULL, `high_scale` NUMERIC(14, 4) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'),
            // DBAL 4.3 (see https://github.com/doctrine/dbal/pull/6864)
            self::equalTo('CREATE TABLE decimal_model (id INT AUTO_INCREMENT NOT NULL, `decimal` NUMERIC(5, 2) NOT NULL, `high_scale` NUMERIC(14, 4) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'),
        ));
    }

    public function testGetCreateSchemaSql3(): void
    {
        $classes = [$this->_em->getClassMetadata(BooleanModel::class)];

        $tool = new SchemaTool($this->_em);
        $sql  = $tool->getCreateSchemaSql($classes);

        self::assertCount(1, $sql);
        self::assertThat($sql[0], self::logicalOr(
            // DBAL < 4.3
            self::equalTo('CREATE TABLE boolean_model (id INT AUTO_INCREMENT NOT NULL, booleanField TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'),
            // DBAL 4.3 (see https://github.com/doctrine/dbal/pull/6864)
            self::equalTo('CREATE TABLE boolean_model (id INT AUTO_INCREMENT NOT NULL, booleanField TINYINT(1) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'),
        ));
    }

    #[Group('DBAL-204')]
    public function testGetCreateSchemaSql4(): void
    {
        if (! class_exists(Visitor::class)) {
            self::markTestSkipped('Test valid for doctrine/dbal:3.x only.');
        }

        $classes = [$this->_em->getClassMetadata(MysqlSchemaNamespacedEntity::class)];

        $tool = new SchemaTool($this->_em);
        $sql  = $tool->getCreateSchemaSql($classes);

        self::assertCount(0, $sql);
    }
}

#[Table('namespace.entity')]
#[Entity]
class MysqlSchemaNamespacedEntity
{
    /** @var int */
    #[Column(type: 'integer')]
    #[Id]
    #[GeneratedValue]
    public $id;
}
