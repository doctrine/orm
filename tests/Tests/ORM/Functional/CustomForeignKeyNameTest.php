<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Tests\OrmFunctionalTestCase;
use RuntimeException;

use function array_map;
use function implode;

class CustomForeignKeyNameTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            CustomFKArticle::class,
            CustomFKAuthor::class,
            CustomFKTag::class,
        ]);
    }

    public function testManyToOneGeneratesCustomForeignKeyName(): void
    {
        $sql       = $this->getSchemaSql();
        $sqlString = implode("\n", $sql);

        // Verify custom FK name is present
        self::assertStringContainsString('fk_custom_article_author', $sqlString);

        // Verify it's a FOREIGN KEY constraint
        self::assertMatchesRegularExpression(
            '/CONSTRAINT\s+(`|")?fk_custom_article_author(`|")?\s+FOREIGN\s+KEY/i',
            $sqlString,
        );
    }

    public function testManyToManyGeneratesCustomForeignKeyNames(): void
    {
        $sql       = $this->getSchemaSql();
        $sqlString = implode("\n", $sql);

        // Verify both FK names
        self::assertStringContainsString('fk_article_tag_article', $sqlString);
        self::assertStringContainsString('fk_article_tag_tag', $sqlString);
    }

    public function testCompositeForeignKeyWithCustomName(): void
    {
        $sql       = $this->getSchemaSql();
        $sqlString = implode("\n", $sql);

        // Verify composite FK has custom name
        self::assertStringContainsString('fk_article_composite_author', $sqlString);
    }

    public function testMultipleCompositeFKNamesThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Only one JoinColumn');

        $this->createSchemaForModels(
            InvalidCompositeFKArticle::class,
            CustomFKAuthor::class,
        );
    }

    public function testPartialCompositeFKNamesIsValid(): void
    {
        // This should now be VALID - only one JoinColumn specifies foreignKeyName
        $this->createSchemaForModels(
            PartialCompositeFKArticle::class,
            CustomFKAuthor::class,
        );

        $sql       = $this->getSchemaSqlForEntities([
            PartialCompositeFKArticle::class,
            CustomFKAuthor::class,
        ]);
        $sqlString = implode("\n", $sql);

        // Verify custom FK name is present
        self::assertStringContainsString('fk_partial', $sqlString);
    }

    public function testCompositeForeignKeyWithSecondPositionCustomName(): void
    {
        $this->createSchemaForModels(
            SecondPositionCompositeFKArticle::class,
            CustomFKAuthor::class,
        );

        $sql       = $this->getSchemaSqlForEntities([
            SecondPositionCompositeFKArticle::class,
            CustomFKAuthor::class,
        ]);
        $sqlString = implode("\n", $sql);

        // Verify composite FK has custom name
        self::assertStringContainsString('fk_second_position', $sqlString);
    }

    /** @return string[] */
    private function getSchemaSql(): array
    {
        $schemaTool = new SchemaTool($this->_em);
        $metadata   = [
            $this->_em->getClassMetadata(CustomFKArticle::class),
            $this->_em->getClassMetadata(CustomFKAuthor::class),
            $this->_em->getClassMetadata(CustomFKTag::class),
        ];

        return $schemaTool->getCreateSchemaSql($metadata);
    }

    /**
     * @param list<class-string> $entityClasses
     *
     * @return string[]
     */
    private function getSchemaSqlForEntities(array $entityClasses): array
    {
        $schemaTool = new SchemaTool($this->_em);
        $metadata   = array_map(
            fn ($class) => $this->_em->getClassMetadata($class),
            $entityClasses,
        );

        return $schemaTool->getCreateSchemaSql($metadata);
    }
}

#[ORM\Entity]
#[ORM\Table(name: 'custom_fk_article')]
class CustomFKArticle
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public int|null $id = null;

    #[ORM\Column(type: 'string')]
    public string $title;

    // Simple ManyToOne with custom FK name
    #[ORM\ManyToOne(targetEntity: CustomFKAuthor::class)]
    #[ORM\JoinColumn(
        name: 'author_id',
        referencedColumnName: 'id',
        foreignKeyName: 'fk_custom_article_author',
    )]
    public CustomFKAuthor|null $author = null;

    // Composite FK with custom name (only first JoinColumn specifies foreignKeyName)
    #[ORM\ManyToOne(targetEntity: CustomFKAuthor::class)]
    #[ORM\JoinColumn(
        name: 'composite_author_id',
        referencedColumnName: 'id',
        foreignKeyName: 'fk_article_composite_author',
    )]
    #[ORM\JoinColumn(
        name: 'composite_author_country',
        referencedColumnName: 'country',
    )]
    public CustomFKAuthor|null $compositeAuthor = null;

    // ManyToMany with custom FK names
    /** @var Collection<int, CustomFKTag> */
    #[ORM\ManyToMany(targetEntity: CustomFKTag::class)]
    #[ORM\JoinTable(
        name: 'article_tag',
        joinColumns: new ORM\JoinColumn(name: 'article_id', referencedColumnName: 'id'),
        inverseJoinColumns: new ORM\JoinColumn(name: 'tag_id', referencedColumnName: 'id'),
        foreignKeyName: 'fk_article_tag_article',
        inverseForeignKeyName: 'fk_article_tag_tag',
    )]
    public Collection $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }
}

#[ORM\Entity]
#[ORM\Table(name: 'custom_fk_author')]
#[ORM\UniqueConstraint(
    name: 'unique_author_country',
    columns: ['id', 'country'],
)]
class CustomFKAuthor
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public int|null $id = null;

    #[ORM\Column(type: 'string', length: 2)]
    public string $country;

    #[ORM\Column(type: 'string', length: 100)]
    public string $name;
}

#[ORM\Entity]
#[ORM\Table(name: 'custom_fk_tag')]
class CustomFKTag
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public int|null $id = null;

    #[ORM\Column(type: 'string')]
    public string $name;
}

// Test entity with mismatched FK names (should throw exception)
#[ORM\Entity]
class InvalidCompositeFKArticle
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public int|null $id = null;

    // This should fail validation: different foreignKeyName on each JoinColumn
    #[ORM\ManyToOne(targetEntity: CustomFKAuthor::class)]
    #[ORM\JoinColumn(
        name: 'author_id',
        referencedColumnName: 'id',
        foreignKeyName: 'fk_one',
    )]
    #[ORM\JoinColumn(
        name: 'author_country',
        referencedColumnName: 'country',
        foreignKeyName: 'fk_two',
    )]
    public CustomFKAuthor|null $author = null;
}

// Test entity with partial FK names (should throw exception)
#[ORM\Entity]
class PartialCompositeFKArticle
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public int|null $id = null;

    // This should fail validation: only one JoinColumn has foreignKeyName
    #[ORM\ManyToOne(targetEntity: CustomFKAuthor::class)]
    #[ORM\JoinColumn(
        name: 'author_id',
        referencedColumnName: 'id',
        foreignKeyName: 'fk_partial',
    )]
    #[ORM\JoinColumn(
        name: 'author_country',
        referencedColumnName: 'country',
    )]
    public CustomFKAuthor|null $author = null;
}

#[ORM\Entity]
class SecondPositionCompositeFKArticle
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public int|null $id = null;

    // This should fail validation: only second JoinColumn has foreignKeyName
    #[ORM\ManyToOne(targetEntity: CustomFKAuthor::class)]
    #[ORM\JoinColumn(
        name: 'author_id',
        referencedColumnName: 'id',
    )]
    #[ORM\JoinColumn(
        name: 'author_country',
        referencedColumnName: 'country',
        foreignKeyName: 'fk_second_position',
    )]
    public CustomFKAuthor|null $author = null;
}
