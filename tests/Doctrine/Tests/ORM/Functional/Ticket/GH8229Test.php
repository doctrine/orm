<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Tests\OrmFunctionalTestCase;
use function array_values;
use function count;

/**
 * @group GH-8229
 */
class GH8229Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        $this->setUpEntitySchema(
            [
                GH8229Resource::class,
                GH8229User::class,
                GH8229EntityWithoutDiscriminator::class,
                EntityExtendingGH8229EntityWithoutDiscriminator::class,
            ]
        );
    }

    /**
     * This tests the basic functionality when working with an entity using joined
     * table inheritance and renamed identifier columns. It tests inserts, updates
     * and deletions.
     */
    public function testCorrectColumnNameInParentClassAfterAttributeOveride()
    {
        // Test creation
        $entity     = new GH8229User('foo');
        $identifier = $entity->id;
        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->clear();

        // Test reading (parent)
        $entity = $this->_em->getRepository(GH8229Resource::class)->find($identifier);
        self::assertEquals($identifier, $entity->id);

        // Test update (parent)
        $entity->status = 2;
        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->clear();
        $entity = $this->_em->getRepository(GH8229Resource::class)->find($identifier);
        self::assertEquals(2, $entity->status);
        $this->_em->clear();

        // Test reading (child)
        $entity = $this->_em->getRepository(GH8229User::class)->find($identifier);
        self::assertEquals($identifier, $entity->id);

        // Test update (child)
        $entity->username = 'bar';
        $entity->status   = 3;
        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->clear();
        $entity = $this->_em->getRepository(GH8229User::class)->find($identifier);
        self::assertEquals('bar', $entity->username);
        self::assertEquals(3, $entity->status);

        // Test deletion
        $this->_em->remove($entity);
        $this->_em->flush();
        $this->_em->clear();
    }

    /**
     * This test checks if foreign keys are generated for the schema, when a joined
     * table inheritance is used and the identifier columns in the inheriting class
     * are renamed.
     */
    public function testForeignKeyInChildClassAfterAttributeOveride()
    {
        $schemaTool = new SchemaTool($this->_em);

        $schema = $schemaTool->getSchemaFromMetadata(
            [
                $this->_em->getClassMetadata(GH8229Resource::class),
                $this->_em->getClassMetadata(GH8229User::class),
            ]
        );

        $childTable            = $schema->getTable('gh8229_user');
        $childTableForeignKeys = $childTable->getForeignKeys();

        self::assertCount(1, $childTableForeignKeys);
    }

    /**
     * This test checks if data are completely deleted, when a joined table inheritance is used,
     * and the DBAL component generally supports foreign keys for the current platform, but the
     * foreign keys never existed, have been removed or disabled.
     */
    public function testJoinedTableDeletionWithDisabledForeignKeys()
    {
        // Remove foreign key (if it exists)
        $connection = $this->_em->getConnection();
        $platform   = $connection->getDatabasePlatform();
        if ($platform->supportsForeignKeyConstraints()) {
            $class       = $this->_em->getClassMetadata(GH8229User::class);
            $table       = $this->_schemaTool->getSchemaFromMetadata([$class])->getTable('gh8229_user');
            $foreignKeys = $table->getForeignKeys();

            // Check if really set (seems to be not the case in MariaDB and MySQL)
            if (count($foreignKeys) > 0) {
                $statement = $platform->getDropForeignKeySQL(array_values($foreignKeys)[0], $table);
                $connection->exec($statement);
            }
        }

        // Create entity
        $entity     = new GH8229User('foo');
        $identifier = $entity->id;
        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->clear();

        // Delete entity
        $entity = $this->_em->getRepository(GH8229User::class)->find($identifier);
        $this->_em->remove($entity);
        $this->_em->flush();
        $this->_em->clear();

        // Check if the child entry has been really deleted
        $result    = $connection->executeQuery('SELECT 1 FROM gh8229_user WHERE user_id = ?', [$identifier]);
        $foundRows = count($result->fetchAll()); // Note: $result->rowCount() returns 1 instead of 0 in SQLite!
        self::assertSame(0, $foundRows);
    }

    /**
     * This test checks the SQL generated for a DQL, because in the SELECT part, the JOIN part
     * and the ORDER BY part the wrong column names were used.
     */
    public function testCorrectColumnNamesInSQLFromDQL()
    {
        // Create entity
        $entity     = new GH8229User('foo');
        $identifier = $entity->id;
        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->clear();

        // Query entity
        $dql   = 'SELECT o FROM Doctrine\Tests\ORM\Functional\Ticket\GH8229User o WHERE o.id = :id GROUP BY o.id, o.username ORDER BY o.id ASC';
        $query = $this->_em->createQuery($dql);
        $query = $query->setParameter('id', $identifier);
        self::assertEquals($identifier, $query->getSingleResult()->id);

        // Delete entity
        $entity = $this->_em->getRepository(GH8229User::class)->find($identifier);
        $this->_em->remove($entity);
        $this->_em->flush();
        $this->_em->clear();
    }

    /**
     * This test checks the the right columns are used when creating a pessimistic write lock
     * for an entity with joined table inheritance.
     */
    public function testCorrectColumnNamesInPessimisticWriteLock()
    {
        // Create entity
        $entity     = new GH8229User('foo');
        $identifier = $entity->id;
        $this->_em->persist($entity);
        $this->_em->flush();

        // Test lock
        $this->_em->getConnection()->beginTransaction();
        $this->_em->lock($entity, LockMode::PESSIMISTIC_WRITE);
        $this->_em->getConnection()->commit();
        $this->_em->clear();

        self::assertEquals($identifier, $entity->id);
    }

    /** this test check alias generation not altered by inheritance in ResolvedTargetEntities mapped classes
     */
    public function testBasicEntityPersisterAliasGenerationWithSimpleInheritance()
    {
        $this->expectNotToPerformAssertions();

        $entity = new EntityExtendingGH8229EntityWithoutDiscriminator();
        $this->_em->persist($entity);
        $this->_em->flush();

        $this->_em->getRepository(EntityExtendingGH8229EntityWithoutDiscriminator::class)->findOneBy(['id' => $entity->getId()]);
    }
}


/**
 * @Entity
 * @Table(name="gh8229_resource")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="resource_type", type="string", length=191)
 * @DiscriminatorMap({
 *     "resource"=GH8229Resource::class,
 *     "user"=GH8229User::class,
 * })
 */
abstract class GH8229Resource
{
    /**
     * @Id()
     * @Column(name="resource_id", type="integer")
     */
    public $id;

    /**
     * Additional property to test update
     *
     * @Column(type="integer", name="resource_status", nullable=false)
     */
    public $status;

    private static $sequence = 0;

    protected function __construct()
    {
        $this->id     = ++self::$sequence;
        $this->status = 1;
    }
}

/**
 * @Entity
 * @Table(name="gh8229_user")
 * @AttributeOverrides({
 *     @AttributeOverride(name="id", column=@Column(name="user_id", type="integer")),
 *     @AttributeOverride(name="status", column=@Column(name="user_status", type="integer"))
 * })
 */
final class GH8229User extends GH8229Resource
{
    /**
     * Additional property to test update
     *
     * @Column(type="string", name="username", length=191, nullable=false)
     */
    public $username;

    public function __construct($username)
    {
        parent::__construct();

        $this->username = $username;
    }
}

/**
 * @Entity
 * @Table(name="gh8229_replacedentity")
 */
class GH8229EntityWithoutDiscriminator
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    public function getId()
    {
        return $this->id;
    }
}

/**
 * @Entity
 */
final class EntityExtendingGH8229EntityWithoutDiscriminator extends GH8229EntityWithoutDiscriminator
{
}
