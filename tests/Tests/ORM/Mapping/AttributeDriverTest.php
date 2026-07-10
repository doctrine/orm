<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Attribute;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Encrypt\EncryptQuery;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\JoinColumnMapping;
use Doctrine\ORM\Mapping\MappingAttribute;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Persistence\Mapping\Driver\ClassNames;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Tests\Mocks\AttributeDriverFactory;
use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\ORM\Mapping\Fixtures\AttributeEntityWithNestedJoinColumns;
use Doctrine\Tests\ORM\Mapping\Fixtures\CompositeIdWithPosition;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

class AttributeDriverTest extends MappingDriverTestCase
{
    protected function loadDriver(): MappingDriver
    {
        return AttributeDriverFactory::createAttributeDriver();
    }

    public function testDriverCanAcceptClassLocator(): void
    {
        if (! AttributeDriverFactory::isClassLocatorSupported()) {
            self::markTestSkipped('This test is only relevant for versions of doctrine/persistence >= 4.1');
        }

        $classLocator = new ClassNames([City::class]);

        $driver = new AttributeDriver($classLocator);

        self::assertSame([], $driver->getPaths(), 'Directory paths must be empty, since file paths are used');
        self::assertSame([City::class], $driver->getAllClassNames());
    }

    public function testOriginallyNestedAttributesDeclaredWithoutOriginalParent(): void
    {
        $factory = $this->createClassMetadataFactory();

        $metadata = $factory->getMetadataFor(AttributeEntityWithoutOriginalParents::class);

        self::assertEquals(
            [
                'name' => 'AttributeEntityWithoutOriginalParents',
                'uniqueConstraints' => ['foo' => ['columns' => ['id']]],
                'indexes' => ['bar' => ['columns' => ['id']]],
            ],
            $metadata->table,
        );
        self::assertEquals(['assoz_id', 'assoz_id'], $metadata->associationMappings['assoc']->joinTableColumns);
    }

    public function testIsTransient(): void
    {
        $driver = $this->loadDriver();

        self::assertTrue($driver->isTransient(stdClass::class));

        self::assertTrue($driver->isTransient(AttributeTransientClass::class));

        self::assertFalse($driver->isTransient(AttributeEntityWithoutOriginalParents::class));

        self::assertFalse($driver->isTransient(AttributeEntityStartingWithRepeatableAttributes::class));
    }

    public function testManyToManyAssociationWithNestedJoinColumns(): void
    {
        $factory = $this->createClassMetadataFactory();

        $metadata = $factory->getMetadataFor(AttributeEntityWithNestedJoinColumns::class);

        self::assertEquals(
            [
                JoinColumnMapping::fromMappingArray([
                    'name' => 'assoz_id',
                    'referencedColumnName' => 'assoz_id',
                    'unique' => false,
                    'nullable' => null,
                    'onDelete' => null,
                    'columnDefinition' => null,
                ]),
            ],
            $metadata->associationMappings['assoc']->joinTable->joinColumns,
        );

        self::assertEquals(
            [
                JoinColumnMapping::fromMappingArray([
                    'name' => 'inverse_assoz_id',
                    'referencedColumnName' => 'inverse_assoz_id',
                    'unique' => false,
                    'nullable' => null,
                    'onDelete' => null,
                    'columnDefinition' => null,
                ]),
            ],
            $metadata->associationMappings['assoc']->joinTable->inverseJoinColumns,
        );
    }

    public function testCompositeIdPositionOrdering(): void
    {
        $factory = $this->createClassMetadataFactory();

        $metadata = $factory->getMetadataFor(CompositeIdWithPosition::class);

        self::assertSame(['first', 'second'], $metadata->identifier);
    }

    public function testItThrowsWhenSettingReportFieldsWhereDeclaredToFalse(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AttributeDriver([], false);
    }

    public function testClassLevelEncryptAttributeSetsMetadataEncrypt(): void
    {
        $factory  = $this->createClassMetadataFactory();
        $metadata = $factory->getMetadataFor(AttributeEntityWithClassLevelEncrypt::class);

        self::assertNotNull($metadata->encrypt);
        self::assertNotNull($metadata->fieldMappings['name']->encrypt);
    }

    public function testClassLevelEncryptAttributeWithSecondLevelCacheThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            'Because encrypted entity "Doctrine\Tests\ORM\Mapping\AttributeEntityWithClassLevelEncryptAndCache", second level cache is not supported on entity.',
        );

        $this->createClassMetadataFactory()->getMetadataFor(AttributeEntityWithClassLevelEncryptAndCache::class);
    }

    public function testFieldLevelEncryptAttributeSetsFieldEncrypt(): void
    {
        $factory  = $this->createClassMetadataFactory();
        $metadata = $factory->getMetadataFor(AttributeEntityWithFieldLevelEncrypt::class);

        self::assertNotNull($metadata->fieldMappings['secret']->encrypt);
        self::assertNull($metadata->fieldMappings['name']->encrypt);
    }

    public function testEncryptAttributeConstructorArgumentsArePropagatedToTheFieldMapping(): void
    {
        $factory  = $this->createClassMetadataFactory();
        $metadata = $factory->getMetadataFor(AttributeEntityWithConfiguredFieldLevelEncrypt::class);

        $encrypt = $metadata->fieldMappings['secret']->encrypt;

        self::assertNotNull($encrypt);
        self::assertSame('my-cipher', $encrypt->cipher);
        self::assertSame('my-key-provider', $encrypt->keyProvider);
        self::assertSame(EncryptQuery::Equality, $encrypt->queryType);
    }

    /** @return iterable<string, array{class-string}> */
    public static function encryptOnAssociationEntitiesProvider(): iterable
    {
        yield 'OneToOne' => [AttributeEntityWithEncryptOnOneToOne::class];
        yield 'OneToMany' => [AttributeEntityWithEncryptOnOneToMany::class];
        yield 'ManyToOne' => [AttributeEntityWithEncryptOnManyToOne::class];
        yield 'ManyToMany' => [AttributeEntityWithEncryptOnManyToMany::class];
    }

    /** @param class-string $className */
    #[DataProvider('encryptOnAssociationEntitiesProvider')]
    public function testEncryptAttributeOnAssociationThrowsException(string $className): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Encrypted association "assoc" on entity "' . $className . '" is not allowed.');

        $this->createClassMetadataFactory()->getMetadataFor($className);
    }

    public function testEncryptAttributeOnEmbeddedIsCarriedThroughToEmbeddedMapping(): void
    {
        $factory  = $this->createClassMetadataFactory();
        $metadata = $factory->getMetadataFor(AttributeEntityWithEncryptOnEmbedded::class);

        self::assertNotNull($metadata->embeddedClasses['contact']->encrypt);
        self::assertNotNull($metadata->getFieldMapping('contact.email')->encrypt);
    }
}

#[ORM\Entity]
#[ORM\Encrypt]
class AttributeEntityWithClassLevelEncrypt
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public int $id;

    #[ORM\Column]
    public string $name;
}

#[ORM\Entity]
#[ORM\Cache]
#[ORM\Encrypt]
class AttributeEntityWithClassLevelEncryptAndCache
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public int $id;
}

#[ORM\Entity]
class AttributeEntityWithFieldLevelEncrypt
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public int $id;

    #[ORM\Column]
    #[ORM\Encrypt]
    public string $secret;

    #[ORM\Column]
    public string $name;
}

#[ORM\Entity]
class AttributeEntityWithConfiguredFieldLevelEncrypt
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public int $id;

    #[ORM\Column]
    #[ORM\Encrypt(cipher: 'my-cipher', keyProvider: 'my-key-provider', queryType: EncryptQuery::Equality)]
    public string $secret;
}

#[ORM\Entity]
class AttributeEntityWithEncryptOnOneToOne
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public int $id;

    #[ORM\OneToOne(targetEntity: self::class)]
    #[ORM\Encrypt]
    public self|null $assoc = null;
}

#[ORM\Entity]
class AttributeEntityWithEncryptOnOneToMany
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public int $id;

    /** @var Collection<int, AttributeEntityWithEncryptOnOneToMany> */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'assoc')]
    #[ORM\Encrypt]
    public Collection $assoc;
}

#[ORM\Entity]
class AttributeEntityWithEncryptOnManyToOne
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public int $id;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\Encrypt]
    public self|null $assoc = null;
}

#[ORM\Entity]
class AttributeEntityWithEncryptOnManyToMany
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public int $id;

    /** @var Collection<int, AttributeEntityWithEncryptOnManyToMany> */
    #[ORM\ManyToMany(targetEntity: self::class)]
    #[ORM\Encrypt]
    public Collection $assoc;
}

#[ORM\Embeddable]
class AttributeEncryptableEmbeddable
{
    #[ORM\Column]
    public string|null $email = null;
}

#[ORM\Entity]
class AttributeEntityWithEncryptOnEmbedded
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public int $id;

    #[ORM\Embedded(class: AttributeEncryptableEmbeddable::class)]
    #[ORM\Encrypt]
    public AttributeEncryptableEmbeddable|null $contact = null;
}

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'foo', columns: ['id'])]
#[ORM\Index(name: 'bar', columns: ['id'])]
class AttributeEntityWithoutOriginalParents
{
    /** @var int */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public $id;

    /** @var Collection<AttributeEntityWithoutOriginalParents> */
    #[ORM\ManyToMany(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'assoz_id', referencedColumnName: 'assoz_id')]
    #[ORM\InverseJoinColumn(name: 'assoz_id', referencedColumnName: 'assoz_id')]
    public $assoc;
}

#[ORM\Index(name: 'bar', columns: ['id'])]
#[ORM\Index(name: 'baz', columns: ['id'])]
#[ORM\Entity]
class AttributeEntityStartingWithRepeatableAttributes
{
}

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_ALL)]
class AttributeTransientAttribute implements MappingAttribute
{
}

#[AttributeTransientAttribute]
class AttributeTransientClass
{
}
