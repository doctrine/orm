<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC2182Test extends OrmFunctionalTestCase
{
    public function testPassColumnOptionsToJoinColumns(): void
    {
        if (! $this->_em->getConnection()->getDatabasePlatform() instanceof MySQLPlatform) {
            self::markTestSkipped('This test is useful for all databases, but designed only for mysql.');
        }

        $sql = $this->_schemaTool->getCreateSchemaSql(
            [
                $this->_em->getClassMetadata(DDC2182OptionParent::class),
                $this->_em->getClassMetadata(DDC2182OptionChild::class),
            ],
        );
        self::assertThat($sql[0], self::logicalOr(
            // DBAL < 4.3
            self::equalTo('CREATE TABLE DDC2182OptionParent (id INT UNSIGNED NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'),
            // DBAL 4.3 (see https://github.com/doctrine/dbal/pull/6864)
            self::equalTo('CREATE TABLE DDC2182OptionParent (id INT UNSIGNED NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'),
        ));
        self::assertThat($sql[1], self::logicalOr(
            // DBAL < 4.3
            self::equalTo('CREATE TABLE DDC2182OptionChild (id VARCHAR(255) NOT NULL, parent_id INT UNSIGNED DEFAULT NULL, INDEX IDX_B314D4AD727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'),
            // DBAL 4.3 (see https://github.com/doctrine/dbal/pull/6864)
            self::equalTo('CREATE TABLE DDC2182OptionChild (id VARCHAR(255) NOT NULL, parent_id INT UNSIGNED DEFAULT NULL, INDEX IDX_B314D4AD727ACA70 (parent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'),
        ));
        self::assertEquals('ALTER TABLE DDC2182OptionChild ADD CONSTRAINT FK_B314D4AD727ACA70 FOREIGN KEY (parent_id) REFERENCES DDC2182OptionParent (id)', $sql[2]);
    }
}

#[Table]
#[Entity]
class DDC2182OptionParent
{
    #[Id]
    #[Column(type: 'integer', options: ['unsigned' => true])]
    private int $id;
}

#[Table]
#[Entity]
class DDC2182OptionChild
{
    #[Id]
    #[Column]
    private string $id;

    #[ManyToOne(targetEntity: 'DDC2182OptionParent')]
    #[JoinColumn(referencedColumnName: 'id')]
    private DDC2182OptionParent $parent;
}
