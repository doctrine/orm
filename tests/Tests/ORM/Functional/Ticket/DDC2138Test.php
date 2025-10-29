<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\ForeignKeyConstraintEditor;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\Table as DbalTable;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

use function array_map;
use function assert;
use function class_exists;
use function reset;

class DDC2138Test extends OrmFunctionalTestCase
{
    /**
     * With this test, we will create the same foreign key twice which is fine, but we will tap a deprecation
     * in DBAL 4.4. This has to be fixed during the validation of metadata. For now, we will simply ignore that
     * deprecation.
     */
    #[Group('DDC-2138')]
    #[IgnoreDeprecations]
    public function testForeignKeyOnSTIWithMultipleMapping(): void
    {
        $schema = $this->getSchemaForModels(
            DDC2138User::class,
            DDC2138Structure::class,
            DDC2138UserFollowedObject::class,
            DDC2138UserFollowedStructure::class,
            DDC2138UserFollowedUser::class,
        );
        self::assertTrue($schema->hasTable('users_followed_objects'), 'Table users_followed_objects should exist.');

        $table = $schema->getTable('users_followed_objects');
        assert($table instanceof DbalTable);
        self::assertTrue(self::columnIsIndexed($table, 'object_id'));
        self::assertTrue(self::columnIsIndexed($table, 'user_id'));
        $foreignKeys = $table->getForeignKeys();
        self::assertCount(1, $foreignKeys, 'user_id column has to have FK, but not object_id');

        $fk = reset($foreignKeys);
        assert($fk instanceof ForeignKeyConstraint);

        if (class_exists(ForeignKeyConstraintEditor::class)) {
            self::assertEquals('users', $fk->getReferencedTableName()->toString());

            $localColumns = array_map(static fn (UnqualifiedName $name) => $name->toString(), $fk->getReferencingColumnNames());
        } else {
            self::assertEquals('users', $fk->getForeignTableName());

            $localColumns = $fk->getLocalColumns();
        }

        self::assertContains('user_id', $localColumns);
        self::assertCount(1, $localColumns);
    }

    private static function columnIsIndexed(DbalTable $table, string $column): bool
    {
        foreach ($table->getIndexes() as $index) {
            if ($index->spansColumns([$column])) {
                return true;
            }
        }

        return false;
    }
}



#[Table(name: 'structures')]
#[Entity]
class DDC2138Structure
{
    #[Id]
    #[Column]
    #[GeneratedValue(strategy: 'AUTO')]
    protected int|null $id = null;

    #[Column(length: 32, nullable: true)]
    protected string|null $name = null;
}

#[Table(name: 'users_followed_objects')]
#[Entity]
#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorColumn(name: 'object_type', type: 'smallint')]
#[DiscriminatorMap([4 => 'DDC2138UserFollowedUser', 3 => 'DDC2138UserFollowedStructure'])]
abstract class DDC2138UserFollowedObject
{
    #[Column(name: 'id', type: 'integer')]
    #[Id]
    #[GeneratedValue(strategy: 'AUTO')]
    public int|null $id = null;
}

#[Entity]
class DDC2138UserFollowedStructure extends DDC2138UserFollowedObject
{
    /**
     * Construct a UserFollowedStructure entity
     */
    public function __construct(
        #[ManyToOne(inversedBy: 'followedStructures')]
        #[JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
        public DDC2138User $user,
        #[ManyToOne]
        #[JoinColumn(name: 'object_id', referencedColumnName: 'id', nullable: false)]
        public DDC2138Structure $followedStructure,
    ) {
    }
}

#[Entity]
class DDC2138UserFollowedUser extends DDC2138UserFollowedObject
{
    /**
     * Construct a UserFollowedUser entity
     */
    public function __construct(
        #[ManyToOne(inversedBy: 'followedUsers')]
        #[JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
        public DDC2138User $user,
        #[ManyToOne]
        #[JoinColumn(name: 'object_id', referencedColumnName: 'id', nullable: false)]
        public DDC2138User $followedUser,
    ) {
    }
}

#[Table(name: 'users')]
#[Entity]
class DDC2138User
{
    #[Id]
    #[Column]
    #[GeneratedValue(strategy: 'AUTO')]
    public int|null $id = null;

    #[Column(length: 32, nullable: true)]
    public string|null $name = null;

    /** @var Collection<int, DDC2138UserFollowedUser> */
    #[OneToMany(targetEntity: DDC2138UserFollowedUser::class, mappedBy: 'user', cascade: ['persist'], orphanRemoval: true)]
    public Collection $followedUsers;

    /** @var Collection<int, DDC2138UserFollowedStructure> */
    #[OneToMany(targetEntity: DDC2138UserFollowedStructure::class, mappedBy: 'user', cascade: ['persist'], orphanRemoval: true)]
    public Collection $followedStructures;

    public function __construct()
    {
        $this->followedUsers      = new ArrayCollection();
        $this->followedStructures = new ArrayCollection();
    }
}
