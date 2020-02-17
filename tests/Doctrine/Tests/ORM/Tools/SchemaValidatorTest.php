<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaValidator;
use Doctrine\Tests\OrmTestCase;
use function sprintf;

class SchemaValidatorTest extends OrmTestCase
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var SchemaValidator */
    private $validator;

    public function setUp() : void
    {
        $this->em        = $this->getTestEntityManager();
        $this->validator = new SchemaValidator($this->em);
    }

    /**
     * @dataProvider modelSetProvider
     */
    public function testCmsModelSet(string $path) : void
    {
        $this->em->getConfiguration()
                 ->getMetadataDriverImpl()
                 ->addPaths([$path]);

        self::assertEmpty($this->validator->validateMapping());
    }

    public function modelSetProvider() : array
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

    /**
     * @group DDC-1439
     */
    public function testInvalidManyToManyJoinColumnSchema() : void
    {
        $class1 = $this->em->getClassMetadata(InvalidEntity1::class);
        $class2 = $this->em->getClassMetadata(InvalidEntity2::class);

        $errors = $this->validator->validateClass($class1);

        $message1 = "The inverse join columns of the many-to-many table '%s' have to contain to ALL identifier columns of the target entity '%s', however '%s' are missing.";
        $message2 = "The join columns of the many-to-many table '%s' have to contain to ALL identifier columns of the source entity '%s', however '%s' are missing.";

        self::assertEquals(
            [
                sprintf($message1, 'Entity1Entity2', InvalidEntity2::class, 'key4'),
                sprintf($message2, 'Entity1Entity2', InvalidEntity1::class, 'key2'),
            ],
            $errors
        );
    }

    /**
     * @group DDC-1439
     */
    public function testInvalidToOneJoinColumnSchema() : void
    {
        $class1 = $this->em->getClassMetadata(InvalidEntity1::class);
        $class2 = $this->em->getClassMetadata(InvalidEntity2::class);

        $errors = $this->validator->validateClass($class2);

        $message1 = "The referenced column name '%s' has to be a primary key column on the target entity class '%s'.";
        $message2 = "The join columns of the association '%s' have to match to ALL identifier columns of the target entity '%s', however '%s' are missing.";

        self::assertEquals(
            [
                sprintf($message1, 'id', InvalidEntity1::class),
                sprintf($message2, 'assoc', InvalidEntity1::class, "key1', 'key2"),
            ],
            $errors
        );
    }

    /**
     * @group DDC-1587
     */
    public function testValidOneToOneAsIdentifierSchema() : void
    {
        $class1 = $this->em->getClassMetadata(DDC1587ValidEntity2::class);
        $class2 = $this->em->getClassMetadata(DDC1587ValidEntity1::class);

        $errors = $this->validator->validateClass($class1);

        self::assertEquals([], $errors);
    }

    /**
     * @group DDC-1649
     */
    public function testInvalidTripleAssociationAsKeyMapping() : void
    {
        $classThree = $this->em->getClassMetadata(DDC1649Three::class);
        $errors     = $this->validator->validateClass($classThree);

        $message1 = "Cannot map association %s#%s as identifier, because the target entity '%s' also maps an association as identifier.";
        $message2 = "The referenced column name '%s' has to be a primary key column on the target entity class '%s'.";

        self::assertEquals(
            [
                sprintf($message1, DDC1649Three::class, 'two', DDC1649Two::class),
                sprintf($message2, 'id', DDC1649Two::class),
            ],
            $errors
        );
    }

    /**
     * @group DDC-3274
     */
    public function testInvalidBiDirectionalRelationMappingMissingInversedByAttribute() : void
    {
        $class  = $this->em->getClassMetadata(DDC3274One::class);
        $errors = $this->validator->validateClass($class);

        $message = 'The property %s#%s is on the inverse side of a bi-directional relationship, but the '
            . "specified mappedBy association on the target-entity %s#%s does not contain the required 'inversedBy=\"%s\"' attribute.";

        self::assertEquals(
            [sprintf($message, DDC3274One::class, 'two', DDC3274Two::class, 'one', 'two')],
            $errors
        );
    }

    /**
     * @group DDC-3322
     */
    public function testInvalidOrderByInvalidField() : void
    {
        $class  = $this->em->getClassMetadata(DDC3322One::class);
        $errors = $this->validator->validateClass($class);

        $message = "The association %s#%s is ordered by a property '%s' that is non-existing field on the target entity '%s'.";

        self::assertEquals(
            [sprintf($message, DDC3322One::class, 'invalidAssoc', 'invalidField', DDC3322ValidEntity1::class)],
            $errors
        );
    }

    /**
     * @group DDC-3322
     */
    public function testInvalidOrderByCollectionValuedAssociation() : void
    {
        $class  = $this->em->getClassMetadata(DDC3322Two::class);
        $errors = $this->validator->validateClass($class);

        $message = "The association %s#%s is ordered by a property '%s' on '%s' that is a collection-valued association.";

        self::assertEquals(
            [sprintf($message, DDC3322Two::class, 'invalidAssoc', 'oneToMany', DDC3322ValidEntity1::class)],
            $errors
        );
    }

    /**
     * @group DDC-3322
     */
    public function testInvalidOrderByAssociationInverseSide() : void
    {
        $class  = $this->em->getClassMetadata(DDC3322Three::class);
        $errors = $this->validator->validateClass($class);

        $message = "The association %s#%s is ordered by a property '%s' on '%s' that is the inverse side of an association.";

        self::assertEquals(
            [sprintf($message, DDC3322Three::class, 'invalidAssoc', 'oneToOneInverse', DDC3322ValidEntity1::class)],
            $errors
        );
    }

    public function testInvalidReferencedJoinTableColumnIsNotPrimary() : void
    {
        $class  = $this->em->getClassMetadata(InvalidEntity3::class);
        $errors = $this->validator->validateClass($class);

        $message = "The referenced column name '%s' has to be a primary key column on the target entity class '%s'.";

        self::assertEquals(
            [sprintf($message, 'nonId4', InvalidEntity4::class)],
            $errors
        );
    }
}

/**
 * @ORM\Entity
 */
class InvalidEntity1
{
    /** @ORM\Id @ORM\Column */
    protected $key1;
    /** @ORM\Id @ORM\Column */
    protected $key2;
    /**
     * @ORM\ManyToMany (targetEntity=InvalidEntity2::class)
     * @ORM\JoinTable (name="Entity1Entity2",
     *      joinColumns={@ORM\JoinColumn(name="key1", referencedColumnName="key1")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="key3", referencedColumnName="key3")}
     *      )
     */
    protected $entity2;
}

/**
 * @ORM\Entity
 */
class InvalidEntity2
{
    /** @ORM\Id @ORM\Column */
    protected $key3;

    /** @ORM\Id @ORM\Column */
    protected $key4;

    /** @ORM\ManyToOne(targetEntity=InvalidEntity1::class) */
    protected $assoc;
}

/**
 * @ORM\Entity
 */
class InvalidEntity3
{
    /** @ORM\Id @ORM\Column */
    protected $id3;

    /**
     * @ORM\ManyToMany(targetEntity=InvalidEntity4::class)
     * @ORM\JoinTable(name="invalid_entity_3_4")
     * @ORM\JoinTable (name="Entity1Entity2",
     *      joinColumns={@ORM\JoinColumn(name="id3_fk", referencedColumnName="id3")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="id4_fk", referencedColumnName="nonId4")}
     * )
     */
    protected $invalid4;
}

/**
 * @ORM\Entity
 */
class InvalidEntity4
{
    /** @ORM\Id @ORM\Column */
    protected $id4;
    /** @ORM\Column */
    protected $nonId4;
}

/**
 * @ORM\Entity(repositoryClass="Entity\Repository\Agent")
 * @ORM\Table(name="agent")
 */
class DDC1587ValidEntity1
{
    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(name="pk", type="integer")
     *
     * @var int
     */
    private $pk;

    /**
     * @ORM\Column(name="name", type="string", length=32)
     *
     * @var string
     */
    private $name;

    /**
     * @ORM\OneToOne(targetEntity=DDC1587ValidEntity2::class, cascade={"all"}, mappedBy="agent")
     * @ORM\JoinColumn(name="pk", referencedColumnName="pk_agent")
     *
     * @var Identifier
     */
    private $identifier;
}

/**
 * @ORM\Entity
 * @ORM\Table
 */
class DDC1587ValidEntity2
{
    /**
     * @ORM\Id
     * @ORM\OneToOne(targetEntity=DDC1587ValidEntity1::class, inversedBy="identifier")
     * @ORM\JoinColumn(name="pk_agent", referencedColumnName="pk", nullable=false)
     *
     * @var DDC1587ValidEntity1
     */
    private $agent;

    /**
     * @ORM\Column(name="num", type="string", length=16, nullable=true)
     *
     * @var string
     */
    private $num;
}

/**
 * @ORM\Entity
 */
class DDC1649One
{
    /** @ORM\Id @ORM\Column @ORM\GeneratedValue */
    public $id;
}

/**
 * @ORM\Entity
 */
class DDC1649Two
{
    /** @ORM\Id @ORM\ManyToOne(targetEntity=DDC1649One::class)@ORM\JoinColumn(name="id", referencedColumnName="id")  */
    public $one;
}

/**
 * @ORM\Entity
 */
class DDC1649Three
{
    /** @ORM\Id @ORM\ManyToOne(targetEntity=DDC1649Two::class) @ORM\JoinColumn(name="id",  referencedColumnName="id") */
    private $two;
}

/**
 * @ORM\Entity
 */
class DDC3274One
{
    /** @ORM\Id @ORM\Column @ORM\GeneratedValue */
    private $id;

    /** @ORM\OneToMany(targetEntity=DDC3274Two::class, mappedBy="one") */
    private $two;
}

/**
 * @ORM\Entity
 */
class DDC3274Two
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=DDC3274One::class)
     */
    private $one;
}

/**
 * @ORM\Entity
 */
class DDC3322ValidEntity1
{
    /** @ORM\Id @ORM\Column @ORM\GeneratedValue */
    private $id;

    /** @ORM\ManyToOne(targetEntity=DDC3322One::class, inversedBy="validAssoc") */
    private $oneValid;

    /** @ORM\ManyToOne(targetEntity=DDC3322One::class, inversedBy="invalidAssoc") */
    private $oneInvalid;

    /** @ORM\ManyToOne(targetEntity=DDC3322Two::class, inversedBy="validAssoc") */
    private $twoValid;

    /** @ORM\ManyToOne(targetEntity=DDC3322Two::class, inversedBy="invalidAssoc") */
    private $twoInvalid;

    /** @ORM\ManyToOne(targetEntity=DDC3322Three::class, inversedBy="validAssoc") */
    private $threeValid;

    /** @ORM\ManyToOne(targetEntity=DDC3322Three::class, inversedBy="invalidAssoc") */
    private $threeInvalid;

    /** @ORM\OneToMany(targetEntity=DDC3322ValidEntity2::class, mappedBy="manyToOne") */
    private $oneToMany;

    /** @ORM\ManyToOne(targetEntity=DDC3322ValidEntity2::class, inversedBy="oneToMany") */
    private $manyToOne;

    /** @ORM\OneToOne(targetEntity=DDC3322ValidEntity2::class, mappedBy="oneToOneOwning") */
    private $oneToOneInverse;

    /** @ORM\OneToOne(targetEntity=DDC3322ValidEntity2::class, inversedBy="oneToOneInverse") */
    private $oneToOneOwning;
}

/**
 * @ORM\Entity
 */
class DDC3322ValidEntity2
{
    /** @ORM\Id @ORM\Column @ORM\GeneratedValue */
    private $id;

    /** @ORM\ManyToOne(targetEntity=DDC3322ValidEntity1::class, inversedBy="oneToMany") */
    private $manyToOne;

    /** @ORM\OneToMany(targetEntity=DDC3322ValidEntity1::class, mappedBy="manyToOne") */
    private $oneToMany;

    /** @ORM\OneToOne(targetEntity=DDC3322ValidEntity1::class, inversedBy="oneToOneInverse") */
    private $oneToOneOwning;

    /** @ORM\OneToOne(targetEntity=DDC3322ValidEntity1::class, mappedBy="oneToOneOwning") */
    private $oneToOneInverse;
}

/**
 * @ORM\Entity
 */
class DDC3322One
{
    /** @ORM\Id @ORM\Column @ORM\GeneratedValue */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity=DDC3322ValidEntity1::class, mappedBy="oneValid")
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $validAssoc;

    /**
     * @ORM\OneToMany(targetEntity=DDC3322ValidEntity1::class, mappedBy="oneInvalid")
     * @ORM\OrderBy({"invalidField" = "ASC"})
     */
    private $invalidAssoc;
}

/**
 * @ORM\Entity
 */
class DDC3322Two
{
    /** @ORM\Id @ORM\Column @ORM\GeneratedValue */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity=DDC3322ValidEntity1::class, mappedBy="twoValid")
     * @ORM\OrderBy({"manyToOne" = "ASC"})
     */
    private $validAssoc;

    /**
     * @ORM\OneToMany(targetEntity=DDC3322ValidEntity1::class, mappedBy="twoInvalid")
     * @ORM\OrderBy({"oneToMany" = "ASC"})
     */
    private $invalidAssoc;
}

/**
 * @ORM\Entity
 */
class DDC3322Three
{
    /** @ORM\Id @ORM\Column @ORM\GeneratedValue */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity=DDC3322ValidEntity1::class, mappedBy="threeValid")
     * @ORM\OrderBy({"oneToOneOwning" = "ASC"})
     */
    private $validAssoc;

    /**
     * @ORM\OneToMany(targetEntity=DDC3322ValidEntity1::class, mappedBy="threeInvalid")
     * @ORM\OrderBy({"oneToOneInverse" = "ASC"})
     */
    private $invalidAssoc;
}
