<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;

use function method_exists;

class GH6823Test extends OrmFunctionalTestCase
{
    public function testCharsetCollationWhenCreatingForeignRelations(): void
    {
        if (! $this->_em->getConnection()->getDatabasePlatform() instanceof MySQLPlatform) {
            self::markTestSkipped('This test is useful for all databases, but designed only for mysql.');
        }

        if (method_exists(AbstractPlatform::class, 'getGuidExpression')) {
            self::markTestSkipped('Test valid for doctrine/dbal:3.x only.');
        }

        $this->createSchemaForModels(
            GH6823User::class,
            GH6823Group::class,
            GH6823Status::class
        );

        self::assertSQLEquals('CREATE TABLE gh6823_user (id VARCHAR(255) NOT NULL, group_id VARCHAR(255) CHARACTER SET ascii DEFAULT NULL COLLATE `ascii_general_ci`, status_id VARCHAR(255) CHARACTER SET latin1 DEFAULT NULL COLLATE `latin1_bin`, INDEX idx_70dd1774fe54d947 (group_id), INDEX idx_70dd17746bf700bd (status_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_bin` ENGINE = InnoDB', $this->getLastLoggedQuery(4)['sql']);
        self::assertSQLEquals('CREATE TABLE gh6823_group (id VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET ascii COLLATE `ascii_general_ci` ENGINE = InnoDB', $this->getLastLoggedQuery(3)['sql']);
        self::assertSQLEquals('CREATE TABLE gh6823_status (id VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_bin`, PRIMARY KEY(id)) DEFAULT CHARACTER SET koi8r COLLATE `koi8r_bin` ENGINE = InnoDB', $this->getLastLoggedQuery(2)['sql']);
        self::assertSQLEquals('ALTER TABLE gh6823_user ADD CONSTRAINT fk_70dd1774fe54d947 FOREIGN KEY (group_id) REFERENCES gh6823_group (id)', $this->getLastLoggedQuery(1)['sql']);
        self::assertSQLEquals('ALTER TABLE gh6823_user ADD CONSTRAINT fk_70dd17746bf700bd FOREIGN KEY (status_id) REFERENCES gh6823_status (id)', $this->getLastLoggedQuery(0)['sql']);
    }
}

/**
 * @Entity
 * @Table(name="gh6823_user", options={
 *     "charset"="utf8mb4",
 *     "collation"="utf8mb4_bin"
 * })
 */
class GH6823User
{
    /**
     * @var string
     * @Id
     * @Column(type="string")
     */
    public $id;

    /**
     * @var GH6823Group
     * @ManyToOne(targetEntity="GH6823Group")
     */
    public $group;

    /**
     * @var GH6823Status
     * @ManyToOne(targetEntity="GH6823Status")
     */
    public $status;
}

/**
 * @Entity
 * @Table(name="gh6823_group", options={
 *     "charset"="ascii",
 *     "collation"="ascii_general_ci"
 * })
 */
class GH6823Group
{
    /**
     * @var string
     * @Id
     * @Column(type="string")
     */
    public $id;
}

/**
 * @Entity
 * @Table(name="gh6823_status", options={
 *     "charset"="koi8r",
 *     "collation"="koi8r_bin"
 * })
 */
class GH6823Status
{
    /**
     * @var string
     * @Id
     * @Column(type="string", options={"charset"="latin1", "collation"="latin1_bin"})
     */
    public $id;
}
