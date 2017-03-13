<?php

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaValidator;
use Doctrine\Tests\OrmTestCase;

class SchemaValidatorTest extends OrmTestCase
{
    /**
     * @var EntityManager
     */
    private $em = null;

    /**
     * @var SchemaValidator
     */
    private $validator = null;

    public function setUp()
    {
        $this->em = $this->getTestEntityManager();
        $this->validator = new SchemaValidator($this->em);
    }

    /**
     * @dataProvider modelSetProvider
     */
    public function testCmsModelSet(string $path)
    {
        $this->em->getConfiguration()
                 ->getMetadataDriverImpl()
                 ->addPaths([$path]);

        self::assertEmpty($this->validator->validateMapping());
    }

    public function modelSetProvider(): array
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
    public function testInvalidManyToManyJoinColumnSchema()
    {
        $class1 = $this->em->getClassMetadata(InvalidEntity1::class);
        $class2 = $this->em->getClassMetadata(InvalidEntity2::class);

        $ce = $this->validator->validateClass($class1);

        $message1 = "The inverse join columns of the many-to-many table '%s' have to contain to ALL identifier columns of the target entity '%s', however '%s' are missing.";
        $message2 = "The join columns of the many-to-many table '%s' have to contain to ALL identifier columns of the source entity '%s', however '%s' are missing.";

        self::assertEquals(
            [
                sprintf($message1, 'Entity1Entity2', InvalidEntity2::class, 'key4'),
                sprintf($message2, 'Entity1Entity2', InvalidEntity1::class, 'key2'),
            ],
            $ce
        );
    }

    /**
     * @group DDC-1439
     */
    public function testInvalidToOneJoinColumnSchema()
    {
        $class1 = $this->em->getClassMetadata(InvalidEntity1::class);
        $class2 = $this->em->getClassMetadata(InvalidEntity2::class);

        $ce = $this->validator->validateClass($class2);

        $message1 = "The referenced column name '%s' has to be a primary key column on the target entity class '%s'.";
        $message2 = "The join columns of the association '%s' have to match to ALL identifier columns of the target entity '%s', however '%s' are missing.";

        self::assertEquals(
            [
                sprintf($message1, 'id', InvalidEntity1::class),
                sprintf($message2, 'assoc', InvalidEntity1::class, "key1', 'key2"),
            ],
            $ce
        );
    }

    /**
     * @group DDC-1587
     */
    public function testValidOneToOneAsIdentifierSchema()
    {
        $class1 = $this->em->getClassMetadata(DDC1587ValidEntity2::class);
        $class2 = $this->em->getClassMetadata(DDC1587ValidEntity1::class);

        $ce = $this->validator->validateClass($class1);

        self::assertEquals([], $ce);
    }

    /**
     * @group DDC-1649
     */
    public function testInvalidTripleAssociationAsKeyMapping()
    {
        $classThree = $this->em->getClassMetadata(DDC1649Three::class);
        $ce = $this->validator->validateClass($classThree);

        $message1 = "Cannot map association %s#%s as identifier, because the target entity '%s' also maps an association as identifier.";
        $message2 = "The referenced column name '%s' has to be a primary key column on the target entity class '%s'.";

        self::assertEquals(
            [
                sprintf($message1, DDC1649Three::class, 'two', DDC1649Two::class),
                sprintf($message2, 'id', DDC1649Two::class),
            ],
            $ce
        );
    }

    /**
     * @group DDC-3274
     */
    public function testInvalidBiDirectionalRelationMappingMissingInversedByAttribute()
    {
        $class = $this->em->getClassMetadata(DDC3274One::class);
        $ce = $this->validator->validateClass($class);

        $message = "The property %s#%s is on the inverse side of a bi-directional relationship, but the "
            . "specified mappedBy association on the target-entity %s#%s does not contain the required 'inversedBy=\"%s\"' attribute.";

        self::assertEquals(
            [
                sprintf($message, DDC3274One::class, 'two', DDC3274Two::class, 'one', 'two')
            ],
            $ce
        );
    }

    /**
     * @group DDC-3322
     */
    public function testInvalidOrderByInvalidField()
    {
        $class = $this->em->getClassMetadata(DDC3322One::class);
        $ce = $this->validator->validateClass($class);

        $message = "The association %s#%s is ordered by a property '%s' that is non-existing field on the target entity '%s'.";

        self::assertEquals(
            [
                sprintf($message, DDC3322One::class, 'invalidAssoc', 'invalidField', DDC3322ValidEntity1::class)
            ],
            $ce
        );
    }

    /**
     * @group DDC-3322
     */
    public function testInvalidOrderByCollectionValuedAssociation()
    {
        $class = $this->em->getClassMetadata(DDC3322Two::class);
        $ce = $this->validator->validateClass($class);

        $message = "The association %s#%s is ordered by a property '%s' on '%s' that is a collection-valued association.";

        self::assertEquals(
            [
                sprintf($message, DDC3322Two::class, 'invalidAssoc', 'oneToMany', DDC3322ValidEntity1::class)
            ],
            $ce
        );
    }

    /**
     * @group DDC-3322
     */
    public function testInvalidOrderByAssociationInverseSide()
    {
        $class = $this->em->getClassMetadata(DDC3322Three::class);
        $ce = $this->validator->validateClass($class);

        $message = "The association %s#%s is ordered by a property '%s' on '%s' that is the inverse side of an association.";

        self::assertEquals(
            [
                sprintf($message, DDC3322Three::class, 'invalidAssoc', 'oneToOneInverse', DDC3322ValidEntity1::class)
            ],
            $ce
        );
    }
}

/**
 * @ORM\Entity
 */
class InvalidEntity1
{
    /**
     * @ORM\Id @ORM\Column
     */
    protected $key1;
    /**
     * @ORM\Id @ORM\Column
     */
    protected $key2;
    /**
     * @ORM\ManyToMany (targetEntity="InvalidEntity2")
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
    /**
     * @ORM\Id @ORM\Column
     */
    protected $key3;

    /**
     * @ORM\Id @ORM\Column
     */
    protected $key4;

    /**
     * @ORM\ManyToOne(targetEntity="InvalidEntity1")
     */
    protected $assoc;
}

/**
 * @ORM\Entity(repositoryClass="Entity\Repository\Agent")
 * @ORM\Table(name="agent")
 */
class DDC1587ValidEntity1
{
    /**
     * @var int
     *
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(name="pk", type="integer")
     */
    private $pk;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=32)
     */
    private $name;

    /**
     * @var Identifier
     *
     * @ORM\OneToOne(targetEntity="DDC1587ValidEntity2", cascade={"all"}, mappedBy="agent")
     * @ORM\JoinColumn(name="pk", referencedColumnName="pk_agent")
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
     * @var DDC1587ValidEntity1
     *
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="DDC1587ValidEntity1", inversedBy="identifier")
     * @ORM\JoinColumn(name="pk_agent", referencedColumnName="pk", nullable=false)
     */
    private $agent;

    /**
     * @var string
     *
     * @ORM\Column(name="num", type="string", length=16, nullable=true)
     */
    private $num;
}

/**
 * @ORM\Entity
 */
class DDC1649One
{
    /**
     * @ORM\Id @ORM\Column @ORM\GeneratedValue
     */
    public $id;
}

/**
 * @ORM\Entity
 */
class DDC1649Two
{
    /** @ORM\Id @ORM\ManyToOne(targetEntity="DDC1649One")@ORM\JoinColumn(name="id", referencedColumnName="id")  */
    public $one;
}

/**
 * @ORM\Entity
 */
class DDC1649Three
{
    /** @ORM\Id @ORM\ManyToOne(targetEntity="DDC1649Two") @ORM\JoinColumn(name="id",
     * referencedColumnName="id") */
    private $two;
}

/**
 * @ORM\Entity
 */
class DDC3274One
{
    /**
     * @ORM\Id @ORM\Column @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity="DDC3274Two", mappedBy="one")
     */
    private $two;
}

/**
 * @ORM\Entity
 */
class DDC3274Two
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="DDC3274One")
     */
    private $one;
}

/**
 * @ORM\Entity
 */
class DDC3322ValidEntity1
{
    /**
     * @ORM\Id @ORM\Column @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="DDC3322One", inversedBy="validAssoc")
     */
    private $oneValid;

    /**
     * @ORM\ManyToOne(targetEntity="DDC3322One", inversedBy="invalidAssoc")
     */
    private $oneInvalid;

    /**
     * @ORM\ManyToOne(targetEntity="DDC3322Two", inversedBy="validAssoc")
     */
    private $twoValid;

    /**
     * @ORM\ManyToOne(targetEntity="DDC3322Two", inversedBy="invalidAssoc")
     */
    private $twoInvalid;

    /**
     * @ORM\ManyToOne(targetEntity="DDC3322Three", inversedBy="validAssoc")
     */
    private $threeValid;

    /**
     * @ORM\ManyToOne(targetEntity="DDC3322Three", inversedBy="invalidAssoc")
     */
    private $threeInvalid;

    /**
     * @ORM\OneToMany(targetEntity="DDC3322ValidEntity2", mappedBy="manyToOne")
     */
    private $oneToMany;

    /**
     * @ORM\ManyToOne(targetEntity="DDC3322ValidEntity2", inversedBy="oneToMany")
     */
    private $manyToOne;

    /**
     * @ORM\OneToOne(targetEntity="DDC3322ValidEntity2", mappedBy="oneToOneOwning")
     */
    private $oneToOneInverse;

    /**
     * @ORM\OneToOne(targetEntity="DDC3322ValidEntity2", inversedBy="oneToOneInverse")
     */
    private $oneToOneOwning;
}

/**
 * @ORM\Entity
 */
class DDC3322ValidEntity2
{
    /**
     * @ORM\Id @ORM\Column @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="DDC3322ValidEntity1", inversedBy="oneToMany")
     */
    private $manyToOne;

    /**
     * @ORM\OneToMany(targetEntity="DDC3322ValidEntity1", mappedBy="manyToOne")
     */
    private $oneToMany;

    /**
     * @ORM\OneToOne(targetEntity="DDC3322ValidEntity1", inversedBy="oneToOneInverse")
     */
    private $oneToOneOwning;

    /**
     * @ORM\OneToOne(targetEntity="DDC3322ValidEntity1", mappedBy="oneToOneOwning")
     */
    private $oneToOneInverse;
}

/**
 * @ORM\Entity
 */
class DDC3322One
{
    /**
     * @ORM\Id @ORM\Column @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity="DDC3322ValidEntity1", mappedBy="oneValid")
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $validAssoc;

    /**
     * @ORM\OneToMany(targetEntity="DDC3322ValidEntity1", mappedBy="oneInvalid")
     * @ORM\OrderBy({"invalidField" = "ASC"})
     */
    private $invalidAssoc;
}

/**
 * @ORM\Entity
 */
class DDC3322Two
{
    /**
     * @ORM\Id @ORM\Column @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity="DDC3322ValidEntity1", mappedBy="twoValid")
     * @ORM\OrderBy({"manyToOne" = "ASC"})
     */
    private $validAssoc;

    /**
     * @ORM\OneToMany(targetEntity="DDC3322ValidEntity1", mappedBy="twoInvalid")
     * @ORM\OrderBy({"oneToMany" = "ASC"})
     */
    private $invalidAssoc;
}

/**
 * @ORM\Entity
 */
class DDC3322Three
{
    /**
     * @ORM\Id @ORM\Column @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity="DDC3322ValidEntity1", mappedBy="threeValid")
     * @ORM\OrderBy({"oneToOneOwning" = "ASC"})
     */
    private $validAssoc;

    /**
     * @ORM\OneToMany(targetEntity="DDC3322ValidEntity1", mappedBy="threeInvalid")
     * @ORM\OrderBy({"oneToOneInverse" = "ASC"})
     */
    private $invalidAssoc;
}
