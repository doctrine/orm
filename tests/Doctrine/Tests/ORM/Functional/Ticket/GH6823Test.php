<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
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

        self::assertQueryLogTail(
            'CREATE TABLE gh6823_user (id VARCHAR(255) NOT NULL, group_id VARCHAR(255) CHARACTER SET ascii DEFAULT NULL COLLATE `ascii_general_ci`, status_id VARCHAR(255) CHARACTER SET latin1 DEFAULT NULL COLLATE `latin1_bin`, INDEX IDX_70DD1774FE54D947 (group_id), INDEX IDX_70DD17746BF700BD (status_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_bin` ENGINE = InnoDB',
            'CREATE TABLE gh6823_user_tags (user_id VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`, tag_id VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_bin`, INDEX IDX_596B1281A76ED395 (user_id), INDEX IDX_596B1281BAD26311 (tag_id), PRIMARY KEY(user_id, tag_id)) DEFAULT CHARACTER SET ascii COLLATE `ascii_general_ci` ENGINE = InnoDB',
            'CREATE TABLE gh6823_group (id VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET ascii COLLATE `ascii_general_ci` ENGINE = InnoDB',
            'CREATE TABLE gh6823_status (id VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_bin`, PRIMARY KEY(id)) DEFAULT CHARACTER SET koi8r COLLATE `koi8r_bin` ENGINE = InnoDB',
            'ALTER TABLE gh6823_user ADD CONSTRAINT FK_70DD1774FE54D947 FOREIGN KEY (group_id) REFERENCES gh6823_group (id)',
            'ALTER TABLE gh6823_user ADD CONSTRAINT FK_70DD17746BF700BD FOREIGN KEY (status_id) REFERENCES gh6823_status (id)',
            'ALTER TABLE gh6823_user_tags ADD CONSTRAINT FK_596B1281A76ED395 FOREIGN KEY (user_id) REFERENCES gh6823_user (id)'
        );
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
     * @Column(type="string", length=255)
     */
    public $id;

    /**
     * @var GH6823Group
     * @ManyToOne(targetEntity="GH6823Group")
     * @JoinColumn(name="group_id", referencedColumnName="id", options={"charset"="ascii", "collation"="ascii_general_ci"})
     */
    public $group;

    /**
     * @var GH6823Status
     * @ManyToOne(targetEntity="GH6823Status")
     * @JoinColumn(name="status_id", referencedColumnName="id", options={"charset"="latin1", "collation"="latin1_bin"})
     */
    public $status;

    /**
     * @var Collection<int, GH6823Tag>
     * @ManyToMany(targetEntity="GH6823Tag")
     * @JoinTable(name="gh6823_user_tags", joinColumns={
     *   @JoinColumn(name="user_id", referencedColumnName="id", options={"charset"="utf8mb4", "collation"="utf8mb4_bin"})
     * }, inverseJoinColumns={
     *   @JoinColumn(name="tag_id", referencedColumnName="id", options={"charset"="latin1", "collation"="latin1_bin"})
     * }, options={"charset"="ascii", "collation"="ascii_general_ci"})
     */
    public $tags;
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
     * @Column(type="string", length=255)
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
     * @Column(type="string", length=255, options={"charset"="latin1", "collation"="latin1_bin"})
     */
    public $id;
}

/**
 * @Entity
 * @Table(name="gh6823_tag", options={
 *     "charset"="koi8r",
 *     "collation"="koi8r_bin"
 * })
 */
class GH6823Tag
{
    /**
     * @var string
     * @Id
     * @Column(type="string", length=255, options={"charset"="latin1", "collation"="latin1_bin"})
     */
    public $id;
}
