<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Embeddable;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\OrderBy;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Tools\SchemaValidator;
use Doctrine\Tests\Models\ECommerce\ECommerceCart;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

class SchemaValidatorTest extends OrmTestCase
{
    private EntityManagerInterface|null $em = null;

    private SchemaValidator|null $validator = null;

    protected function setUp(): void
    {
        $this->em        = $this->getTestEntityManager();
        $this->validator = new SchemaValidator($this->em);
    }

    #[DataProvider('modelSetProvider')]
    public function testCmsModelSet(string $path): void
    {
        $this->em->getConfiguration()
                 ->getMetadataDriverImpl()
                 ->addPaths([$path]);

        self::assertEmpty($this->validator->validateMapping());
    }

    public static function modelSetProvider(): array
    {
        return [
            'cms'        => [__DIR__ . '/../../Models/CMS'],
            'company'    => [__DIR__ . '/../../Models/Company'],
            'ecommerce'  => [__DIR__ . '/../../Models/ECommerce'],
            'forum'      => [__DIR__ . '/../../Models/Forum'],
            'navigation' => [__DIR__ . '/../../Models/Navigation'],
            'routing'    => [__DIR__ . '/../../Models/Routing'],
        ];
    }

    #[Group('DDC-1439')]
    public function testInvalidManyToManyJoinColumnSchema(): void
    {
        $class1 = $this->em->getClassMetadata(InvalidEntity1::class);
        $class2 = $this->em->getClassMetadata(InvalidEntity2::class);

        $ce = $this->validator->validateClass($class1);

        self::assertEquals(
            [
                "The inverse join columns of the many-to-many table 'Entity1Entity2' have to contain to ALL identifier columns of the target entity 'Doctrine\Tests\ORM\Tools\InvalidEntity2', however 'key4' are missing.",
                "The join columns of the many-to-many table 'Entity1Entity2' have to contain to ALL identifier columns of the source entity 'Doctrine\Tests\ORM\Tools\InvalidEntity1', however 'key2' are missing.",
            ],
            $ce,
        );
    }

    #[Group('DDC-1439')]
    public function testInvalidToOneJoinColumnSchema(): void
    {
        $class1 = $this->em->getClassMetadata(InvalidEntity1::class);
        $class2 = $this->em->getClassMetadata(InvalidEntity2::class);

        $ce = $this->validator->validateClass($class2);

        self::assertEquals(
            [
                "The referenced column name 'id' has to be a primary key column on the target entity class 'Doctrine\Tests\ORM\Tools\InvalidEntity1'.",
                "The join columns of the association 'assoc' have to match to ALL identifier columns of the target entity 'Doctrine\Tests\ORM\Tools\InvalidEntity1', however 'key1, key2' are missing.",
            ],
            $ce,
        );
    }

    #[Group('DDC-1587')]
    public function testValidOneToOneAsIdentifierSchema(): void
    {
        $class1 = $this->em->getClassMetadata(DDC1587ValidEntity2::class);
        $class2 = $this->em->getClassMetadata(DDC1587ValidEntity1::class);

        $ce = $this->validator->validateClass($class1);

        self::assertEquals([], $ce);
    }

    #[Group('DDC-1649')]
    public function testInvalidTripleAssociationAsKeyMapping(): void
    {
        $classThree = $this->em->getClassMetadata(DDC1649Three::class);
        $ce         = $this->validator->validateClass($classThree);

        self::assertEquals(
            [
                "Cannot map association 'Doctrine\Tests\ORM\Tools\DDC1649Three#two as identifier, because the target entity 'Doctrine\Tests\ORM\Tools\DDC1649Two' also maps an association as identifier.",
                "The referenced column name 'id' has to be a primary key column on the target entity class 'Doctrine\Tests\ORM\Tools\DDC1649Two'.",
            ],
            $ce,
        );
    }

    #[Group('DDC-3274')]
    public function testInvalidBiDirectionalRelationMappingMissingInversedByAttribute(): void
    {
        $class = $this->em->getClassMetadata(DDC3274One::class);
        $ce    = $this->validator->validateClass($class);

        self::assertEquals(
            [
                'The field Doctrine\Tests\ORM\Tools\DDC3274One#two is on the inverse side of a bi-directional ' .
                'relationship, but the specified mappedBy association on the target-entity ' .
                "Doctrine\Tests\ORM\Tools\DDC3274Two#one does not contain the required 'inversedBy=\"two\"' attribute.",
            ],
            $ce,
        );
    }

    #[Group('9536')]
    public function testInvalidBiDirectionalRelationMappingMissingMappedByAttribute(): void
    {
        $class = $this->em->getClassMetadata(Issue9536Owner::class);
        $ce    = $this->validator->validateClass($class);

        self::assertEquals(
            [
                'The field Doctrine\Tests\ORM\Tools\Issue9536Owner#one is on the owning side of a bi-directional ' .
                'relationship, but the specified inversedBy association on the target-entity ' .
                "Doctrine\Tests\ORM\Tools\Issue9536Target#two does not contain the required 'mappedBy=\"one\"' " .
                'attribute.',
            ],
            $ce,
        );
    }

    #[Group('DDC-3322')]
    public function testInvalidOrderByInvalidField(): void
    {
        $class = $this->em->getClassMetadata(DDC3322One::class);
        $ce    = $this->validator->validateClass($class);

        self::assertEquals(
            [
                'The association Doctrine\Tests\ORM\Tools\DDC3322One#invalidAssoc is ordered by a foreign field ' .
                'invalidField that is not a field on the target entity Doctrine\Tests\ORM\Tools\DDC3322ValidEntity1.',
            ],
            $ce,
        );
    }

    #[Group('DDC-3322')]
    public function testInvalidOrderByCollectionValuedAssociation(): void
    {
        $class = $this->em->getClassMetadata(DDC3322Two::class);
        $ce    = $this->validator->validateClass($class);

        self::assertEquals(
            [
                'The association Doctrine\Tests\ORM\Tools\DDC3322Two#invalidAssoc is ordered by a field oneToMany ' .
                'on Doctrine\Tests\ORM\Tools\DDC3322ValidEntity1 that is a collection-valued association.',
            ],
            $ce,
        );
    }

    #[Group('DDC-3322')]
    public function testInvalidOrderByAssociationInverseSide(): void
    {
        $class = $this->em->getClassMetadata(DDC3322Three::class);
        $ce    = $this->validator->validateClass($class);

        self::assertEquals(
            [
                'The association Doctrine\Tests\ORM\Tools\DDC3322Three#invalidAssoc is ordered by a field oneToOneInverse ' .
                'on Doctrine\Tests\ORM\Tools\DDC3322ValidEntity1 that is the inverse side of an association.',
            ],
            $ce,
        );
    }

    public function testMappedSuperclassNotPresentInDiscriminator(): void
    {
        $class1 = $this->em->getClassMetadata(MappedSuperclassEntity::class);
        $ce     = $this->validator->validateClass($class1);

        $this->assertEquals([], $ce);
    }

    public function testAbstractChildClassNotPresentInDiscriminator(): void
    {
        $class1 = $this->em->getClassMetadata(Issue9095AbstractChild::class);
        $ce     = $this->validator->validateClass($class1);

        self::assertEmpty($ce);
    }

    public function testInvalidAssociationTowardsMappedSuperclass(): void
    {
        $classThree = $this->em->getClassMetadata(InvalidMappedSuperClass::class);
        $ce         = $this->validator->validateClass($classThree);

        self::assertEquals(
            ["The target entity 'Doctrine\Tests\ORM\Tools\InvalidMappedSuperClass' specified on Doctrine\Tests\ORM\Tools\InvalidMappedSuperClass#selfWhatever is a mapped superclass. This is not possible since there is no table that a foreign key could refer to."],
            $ce,
        );
    }
}

#[MappedSuperclass]
abstract class MappedSuperclassEntity extends ParentEntity
{
}

#[Entity]
#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorMap(['child' => ChildEntity::class])]
abstract class ParentEntity
{
    /** @var mixed */
    #[Id]
    #[Column]
    protected $key;
}

#[Entity]
class ChildEntity extends MappedSuperclassEntity
{
}

#[Entity]
class InvalidEntity1
{
    /** @var mixed */
    #[Id]
    #[Column]
    protected $key1;

    /** @var mixed */
    #[Id]
    #[Column]
    protected $key2;

    /** @var ArrayCollection */
    #[JoinTable(name: 'Entity1Entity2')]
    #[JoinColumn(name: 'key1', referencedColumnName: 'key1')]
    #[InverseJoinColumn(name: 'key3', referencedColumnName: 'key3')]
    #[ManyToMany(targetEntity: 'InvalidEntity2')]
    protected $entity2;
}

#[Entity]
class InvalidEntity2
{
    /** @var mixed */
    #[Id]
    #[Column]
    protected $key3;

    /** @var mixed */
    #[Id]
    #[Column]
    protected $key4;

    /** @var InvalidEntity1 */
    #[ManyToOne(targetEntity: 'InvalidEntity1')]
    protected $assoc;
}

#[Table(name: 'agent')]
#[Entity(repositoryClass: 'Entity\Repository\Agent')]
class DDC1587ValidEntity1
{
    #[Id]
    #[GeneratedValue]
    #[Column(name: 'pk', type: 'integer')]
    private int $pk;

    #[Column(name: 'name', type: 'string', length: 32)]
    private string $name;

    /** @var Identifier */
    #[OneToOne(targetEntity: 'DDC1587ValidEntity2', cascade: ['all'], mappedBy: 'agent')]
    private $identifier;
}

#[Table]
#[Entity]
class DDC1587ValidEntity2
{
    #[Id]
    #[OneToOne(targetEntity: 'DDC1587ValidEntity1', inversedBy: 'identifier')]
    #[JoinColumn(name: 'pk_agent', referencedColumnName: 'pk', nullable: false)]
    private DDC1587ValidEntity1 $agent;

    #[Column(name: 'num', type: 'string', length: 16, nullable: true)]
    private string $num;
}

#[Entity]
class DDC1649One
{
    /** @var mixed */
    #[Id]
    #[Column]
    #[GeneratedValue]
    public $id;
}

#[Entity]
class DDC1649Two
{
    /** @var DDC1649One */
    #[Id]
    #[ManyToOne(targetEntity: 'DDC1649One')]
    public $one;
}

#[Entity]
class DDC1649Three
{
    #[Id]
    #[ManyToOne(targetEntity: 'DDC1649Two')]
    #[JoinColumn(name: 'id', referencedColumnName: 'id')]
    private DDC1649Two $two;
}

#[Entity]
class DDC3274One
{
    /** @var mixed */
    #[Id]
    #[Column]
    #[GeneratedValue]
    private $id;

    #[OneToMany(targetEntity: 'DDC3274Two', mappedBy: 'one')]
    private ArrayCollection $two;
}

#[Entity]
class DDC3274Two
{
    #[Id]
    #[ManyToOne(targetEntity: 'DDC3274One')]
    private DDC3274One $one;
}

#[Entity]
class Issue9536Target
{
    /** @var mixed */
    #[Id]
    #[Column]
    #[GeneratedValue]
    private $id;

    #[OneToOne(targetEntity: 'Issue9536Owner')]
    private Issue9536Owner $two;
}

#[Entity]
class Issue9536Owner
{
    /** @var mixed */
    #[Id]
    #[Column]
    #[GeneratedValue]
    private $id;

    #[OneToOne(targetEntity: 'Issue9536Target', inversedBy: 'two')]
    private Issue9536Target $one;
}

#[Entity]
class DDC3322ValidEntity1
{
    /** @var mixed */
    #[Id]
    #[Column]
    #[GeneratedValue]
    private $id;

    #[ManyToOne(targetEntity: 'DDC3322One', inversedBy: 'validAssoc')]
    private DDC3322One $oneValid;

    #[ManyToOne(targetEntity: 'DDC3322One', inversedBy: 'invalidAssoc')]
    private DDC3322One $oneInvalid;

    #[ManyToOne(targetEntity: 'DDC3322Two', inversedBy: 'validAssoc')]
    private DDC3322Two $twoValid;

    #[ManyToOne(targetEntity: 'DDC3322Two', inversedBy: 'invalidAssoc')]
    private DDC3322Two $twoInvalid;

    #[ManyToOne(targetEntity: 'DDC3322Three', inversedBy: 'validAssoc')]
    private DDC3322Three $threeValid;

    #[ManyToOne(targetEntity: 'DDC3322Three', inversedBy: 'invalidAssoc')]
    private DDC3322Three $threeInvalid;

    #[OneToMany(targetEntity: 'DDC3322ValidEntity2', mappedBy: 'manyToOne')]
    private DDC3322ValidEntity2 $oneToMany;

    #[ManyToOne(targetEntity: 'DDC3322ValidEntity2', inversedBy: 'oneToMany')]
    private DDC3322ValidEntity2 $manyToOne;

    #[OneToOne(targetEntity: 'DDC3322ValidEntity2', mappedBy: 'oneToOneOwning')]
    private DDC3322ValidEntity2 $oneToOneInverse;

    #[OneToOne(targetEntity: 'DDC3322ValidEntity2', inversedBy: 'oneToOneInverse')]
    private DDC3322ValidEntity2 $oneToOneOwning;
}

#[Entity]
class DDC3322ValidEntity2
{
    #[Id]
    #[Column]
    #[GeneratedValue]
    private int $id;

    #[ManyToOne(targetEntity: 'DDC3322ValidEntity1', inversedBy: 'oneToMany')]
    private DDC3322ValidEntity1 $manyToOne;

    #[OneToMany(targetEntity: 'DDC3322ValidEntity1', mappedBy: 'manyToOne')]
    private DDC3322ValidEntity1 $oneToMany;

    #[OneToOne(targetEntity: 'DDC3322ValidEntity1', inversedBy: 'oneToOneInverse')]
    private DDC3322ValidEntity1 $oneToOneOwning;

    #[OneToOne(targetEntity: 'DDC3322ValidEntity1', mappedBy: 'oneToOneOwning')]
    private DDC3322ValidEntity1 $oneToOneInverse;
}

#[Entity]
class DDC3322One
{
    #[Id]
    #[Column]
    #[GeneratedValue]
    private int $id;

    /** @psalm-var Collection<int, DDC3322ValidEntity1> */
    #[OneToMany(targetEntity: 'DDC3322ValidEntity1', mappedBy: 'oneValid')]
    #[OrderBy(['id' => 'ASC'])]
    private $validAssoc;

    /** @psalm-var Collection<int, DDC3322ValidEntity1> */
    #[OneToMany(targetEntity: 'DDC3322ValidEntity1', mappedBy: 'oneInvalid')]
    #[OrderBy(['invalidField' => 'ASC'])]
    private $invalidAssoc;
}

#[Entity]
class DDC3322Two
{
    #[Id]
    #[Column]
    #[GeneratedValue]
    private int $id;

    /** @psalm-var Collection<int, DDC3322ValidEntity1> */
    #[OneToMany(targetEntity: 'DDC3322ValidEntity1', mappedBy: 'twoValid')]
    #[OrderBy(['manyToOne' => 'ASC'])]
    private $validAssoc;

    /** @psalm-var Collection<int, DDC3322ValidEntity1> */
    #[OneToMany(targetEntity: 'DDC3322ValidEntity1', mappedBy: 'twoInvalid')]
    #[OrderBy(['oneToMany' => 'ASC'])]
    private $invalidAssoc;
}

#[Entity]
class DDC3322Three
{
    #[Id]
    #[Column]
    #[GeneratedValue]
    private int $id;

    #[OneToMany(targetEntity: 'DDC3322ValidEntity1', mappedBy: 'threeValid')]
    #[OrderBy(['oneToOneOwning' => 'ASC'])]
    private DDC3322ValidEntity1 $validAssoc;

    /** @psalm-var Collection<int, DDC3322ValidEntity1> */
    #[OneToMany(targetEntity: 'DDC3322ValidEntity1', mappedBy: 'threeInvalid')]
    #[OrderBy(['oneToOneInverse' => 'ASC'])]
    private $invalidAssoc;
}

#[Embeddable]
class EmbeddableWithAssociation
{
    #[OneToOne(targetEntity: 'Doctrine\Tests\Models\ECommerce\ECommerceCart')]
    private ECommerceCart $cart;
}

#[Entity]
#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorMap(['child' => Issue9095Child::class])]
abstract class Issue9095Parent
{
    /** @var mixed */
    #[Id]
    #[Column]
    protected $key;
}

#[Entity]
abstract class Issue9095AbstractChild extends Issue9095Parent
{
}

#[Entity]
class Issue9095Child extends Issue9095AbstractChild
{
}

#[MappedSuperclass]
class InvalidMappedSuperClass
{
    /** @psalm-var Collection<int, self> */
    #[ManyToMany(targetEntity: 'InvalidMappedSuperClass', mappedBy: 'invalid')]
    private $selfWhatever;
}
