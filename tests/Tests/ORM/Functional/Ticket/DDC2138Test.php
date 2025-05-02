<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
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
use Doctrine\Tests\ORM\Functional\Ticket\Doctrine\Common\Collections\Collection;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

use function array_map;
use function assert;
use function class_exists;
use function reset;

class DDC2138Test extends OrmFunctionalTestCase
{
    #[Group('DDC-2138')]
    public function testForeignKeyOnSTIWithMultipleMapping(): void
    {
        $em     = $this->_em;
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
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    protected $id;

    /** @var string */
    #[Column(type: 'string', length: 32, nullable: true)]
    protected $name;
}

#[Table(name: 'users_followed_objects')]
#[Entity]
#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorColumn(name: 'object_type', type: 'smallint')]
#[DiscriminatorMap([4 => 'DDC2138UserFollowedUser', 3 => 'DDC2138UserFollowedStructure'])]
abstract class DDC2138UserFollowedObject
{
    /** @var int $id */
    #[Column(name: 'id', type: 'integer')]
    #[Id]
    #[GeneratedValue(strategy: 'AUTO')]
    protected $id;

    /**
     * Get id
     */
    public function getId(): int
    {
        return $this->id;
    }
}

#[Entity]
class DDC2138UserFollowedStructure extends DDC2138UserFollowedObject
{
    /**
     * Construct a UserFollowedStructure entity
     */
    public function __construct(
        #[ManyToOne(targetEntity: 'DDC2138User', inversedBy: 'followedStructures')]
        #[JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
        protected User $user,
        #[ManyToOne(targetEntity: 'DDC2138Structure')]
        #[JoinColumn(name: 'object_id', referencedColumnName: 'id', nullable: false)]
        private Structure $followedStructure,
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Gets followed structure
     */
    public function getFollowedStructure(): Structure
    {
        return $this->followedStructure;
    }
}

#[Entity]
class DDC2138UserFollowedUser extends DDC2138UserFollowedObject
{
    /**
     * Construct a UserFollowedUser entity
     */
    public function __construct(
        #[ManyToOne(targetEntity: 'DDC2138User', inversedBy: 'followedUsers')]
        #[JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
        protected User $user,
        #[ManyToOne(targetEntity: 'DDC2138User')]
        #[JoinColumn(name: 'object_id', referencedColumnName: 'id', nullable: false)]
        private User $followedUser,
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Gets followed user
     */
    public function getFollowedUser(): User
    {
        return $this->followedUser;
    }
}

#[Table(name: 'users')]
#[Entity]
class DDC2138User
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    protected $id;

    /** @var string */
    #[Column(type: 'string', length: 32, nullable: true)]
    protected $name;

    /** @var ArrayCollection $followedUsers */
    #[OneToMany(targetEntity: 'DDC2138UserFollowedUser', mappedBy: 'user', cascade: ['persist'], orphanRemoval: true)]
    protected $followedUsers;

    /** @var ArrayCollection $followedStructures */
    #[OneToMany(targetEntity: 'DDC2138UserFollowedStructure', mappedBy: 'user', cascade: ['persist'], orphanRemoval: true)]
    protected $followedStructures;

    public function __construct()
    {
        $this->followedUsers      = new ArrayCollection();
        $this->followedStructures = new ArrayCollection();
    }

    public function addFollowedUser(UserFollowedUser $followedUsers): User
    {
        $this->followedUsers[] = $followedUsers;

        return $this;
    }

    public function removeFollowedUser(UserFollowedUser $followedUsers): User
    {
        $this->followedUsers->removeElement($followedUsers);

        return $this;
    }

    public function getFollowedUsers(): Collection
    {
        return $this->followedUsers;
    }

    public function addFollowedStructure(UserFollowedStructure $followedStructures): User
    {
        $this->followedStructures[] = $followedStructures;

        return $this;
    }

    public function removeFollowedStructure(UserFollowedStructure $followedStructures): User
    {
        $this->followedStructures->removeElement($followedStructures);

        return $this;
    }

    public function getFollowedStructures(): Collection
    {
        return $this->followedStructures;
    }
}
