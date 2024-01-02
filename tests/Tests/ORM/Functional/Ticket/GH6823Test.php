<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH6823Test extends OrmFunctionalTestCase
{
    public function testCharsetCollationWhenCreatingForeignRelations(): void
    {
        if (! $this->_em->getConnection()->getDatabasePlatform() instanceof MySQLPlatform) {
            self::markTestSkipped('This test is useful for all databases, but designed only for mysql.');
        }

        $this->createSchemaForModels(
            GH6823User::class,
            GH6823Group::class,
            GH6823Status::class,
        );

        $schemaManager = $this->createSchemaManager();
        /* gh6823_user.group_id should use charset ascii and collation
         * ascii_general_ci, because that is what gh6823_group.id falls back to */
        $userGroupIdOptions = $schemaManager->introspectTable('gh6823_user')->getColumn('group_id')->toArray();
        self::assertSame('ascii', $userGroupIdOptions['charset']);
        self::assertSame('ascii_general_ci', $userGroupIdOptions['collation']);

        /* gh6823_user.status_id should use charset latin1 and collation
         * latin1_bin, because that is what gh6823_status.id uses */
        $userStatusIdOptions = $schemaManager->introspectTable('gh6823_user')->getColumn('status_id')->toArray();
        self::assertSame('latin1', $userStatusIdOptions['charset']);
        self::assertSame('latin1_bin', $userStatusIdOptions['collation']);

        /* gh6823_user_tags.user_id should use charset utf8mb4 and collation
         * utf8mb4_bin, because that is what gh6823_user.id falls back to */
        $userTagsUserIdOptions = $schemaManager->introspectTable('gh6823_user_tags')->getColumn('user_id')->toArray();
        self::assertSame('utf8mb4', $userTagsUserIdOptions['charset']);
        self::assertSame('utf8mb4_bin', $userTagsUserIdOptions['collation']);

        /* gh6823_user_tags.tag_id should use charset latin1 and collation
         * latin1_bin, because that is what gh6823_tag.id falls back to */
        $userTagsTagIdOption = $schemaManager->introspectTable('gh6823_user_tags')->getColumn('tag_id')->toArray();
        self::assertSame('latin1', $userTagsTagIdOption['charset']);
        self::assertSame('latin1_bin', $userTagsTagIdOption['collation']);
    }
}

#[Table(name: 'gh6823_user', options: ['charset' => 'utf8mb4', 'collation' => 'utf8mb4_bin'])]
#[Entity]
class GH6823User
{
    /** @var string */
    #[Id]
    #[Column(type: 'string', length: 255)]
    public $id;

    /** @var GH6823Group */
    #[ManyToOne(targetEntity: 'GH6823Group')]
    #[JoinColumn(name: 'group_id', referencedColumnName: 'id', options: ['charset' => 'ascii', 'collation' => 'ascii_general_ci'])]
    public $group;

    /** @var GH6823Status */
    #[ManyToOne(targetEntity: 'GH6823Status')]
    #[JoinColumn(name: 'status_id', referencedColumnName: 'id', options: ['charset' => 'latin1', 'collation' => 'latin1_bin'])]
    public $status;

    /** @var Collection<int, GH6823Tag> */
    #[JoinTable(name: 'gh6823_user_tags', options: ['charset' => 'ascii', 'collation' => 'ascii_general_ci'])]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id', options: ['charset' => 'utf8mb4', 'collation' => 'utf8mb4_bin'])]
    #[InverseJoinColumn(name: 'tag_id', referencedColumnName: 'id', options: ['charset' => 'latin1', 'collation' => 'latin1_bin'])]
    #[ManyToMany(targetEntity: 'GH6823Tag')]
    public $tags;
}

#[Table(name: 'gh6823_group', options: ['charset' => 'ascii', 'collation' => 'ascii_general_ci'])]
#[Entity]
class GH6823Group
{
    /** @var string */
    #[Id]
    #[Column(type: 'string', length: 255)]
    public $id;
}

#[Table(name: 'gh6823_status', options: ['charset' => 'koi8r', 'collation' => 'koi8r_bin'])]
#[Entity]
class GH6823Status
{
    /** @var string */
    #[Id]
    #[Column(type: 'string', length: 255, options: ['charset' => 'latin1', 'collation' => 'latin1_bin'])]
    public $id;
}

#[Table(name: 'gh6823_tag', options: ['charset' => 'koi8r', 'collation' => 'koi8r_bin'])]
#[Entity]
class GH6823Tag
{
    /** @var string */
    #[Id]
    #[Column(type: 'string', length: 255, options: ['charset' => 'latin1', 'collation' => 'latin1_bin'])]
    public $id;
}
