<?php

namespace Doctrine\Tests\ORM\Tools;

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
        $this->em = $this->_getTestEntityManager();
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

        $this->assertEquals(
            [
                "The inverse join columns of the many-to-many table 'Entity1Entity2' have to contain to ALL identifier columns of the target entity 'Doctrine\Tests\ORM\Tools\InvalidEntity2', however 'key4' are missing.",
                "The join columns of the many-to-many table 'Entity1Entity2' have to contain to ALL identifier columns of the source entity 'Doctrine\Tests\ORM\Tools\InvalidEntity1', however 'key2' are missing."
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

        $this->assertEquals(
            [
                "The referenced column name 'id' has to be a primary key column on the target entity class 'Doctrine\Tests\ORM\Tools\InvalidEntity1'.",
                "The join columns of the association 'assoc' have to match to ALL identifier columns of the target entity 'Doctrine\Tests\ORM\Tools\InvalidEntity1', however 'key1, key2' are missing."
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

        $this->assertEquals([], $ce);
    }

    /**
     * @group DDC-1649
     */
    public function testInvalidTripleAssociationAsKeyMapping()
    {
        $classThree = $this->em->getClassMetadata(DDC1649Three::class);
        $ce = $this->validator->validateClass($classThree);

        $this->assertEquals(
            [
            "Cannot map association 'Doctrine\Tests\ORM\Tools\DDC1649Three#two as identifier, because the target entity 'Doctrine\Tests\ORM\Tools\DDC1649Two' also maps an association as identifier.",
            "The referenced column name 'id' has to be a primary key column on the target entity class 'Doctrine\Tests\ORM\Tools\DDC1649Two'."
            ], $ce);
    }

    /**
     * @group DDC-3274
     */
    public function testInvalidBiDirectionalRelationMappingMissingInversedByAttribute()
    {
        $class = $this->em->getClassMetadata(DDC3274One::class);
        $ce = $this->validator->validateClass($class);

        $this->assertEquals(
            [
                "The field Doctrine\Tests\ORM\Tools\DDC3274One#two is on the inverse side of a bi-directional " .
                "relationship, but the specified mappedBy association on the target-entity " .
                "Doctrine\Tests\ORM\Tools\DDC3274Two#one does not contain the required 'inversedBy=\"two\"' attribute."
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

        $this->assertEquals(
            [
                "The association Doctrine\Tests\ORM\Tools\DDC3322One#invalidAssoc is ordered by a foreign field " .
                "invalidField that is not a field on the target entity Doctrine\Tests\ORM\Tools\DDC3322ValidEntity1."
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

        $this->assertEquals(
            [
                "The association Doctrine\Tests\ORM\Tools\DDC3322Two#invalidAssoc is ordered by a field oneToMany " .
                "on Doctrine\Tests\ORM\Tools\DDC3322ValidEntity1 that is a collection-valued association."
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

        $this->assertEquals(
            [
                "The association Doctrine\Tests\ORM\Tools\DDC3322Three#invalidAssoc is ordered by a field oneToOneInverse " .
                "on Doctrine\Tests\ORM\Tools\DDC3322ValidEntity1 that is the inverse side of an association."
            ],
            $ce
        );
    }
}

/**
 * @Entity
 */
class InvalidEntity1
{
    /**
     * @Id @Column
     */
    protected $key1;
    /**
     * @Id @Column
     */
    protected $key2;
    /**
     * @ManyToMany (targetEntity="InvalidEntity2")
     * @JoinTable (name="Entity1Entity2",
     *      joinColumns={@JoinColumn(name="key1", referencedColumnName="key1")},
     *      inverseJoinColumns={@JoinColumn(name="key3", referencedColumnName="key3")}
     *      )
     */
    protected $entity2;
}

/**
 * @Entity
 */
class InvalidEntity2
{
    /**
     * @Id @Column
     */
    protected $key3;

    /**
     * @Id @Column
     */
    protected $key4;

    /**
     * @ManyToOne(targetEntity="InvalidEntity1")
     */
    protected $assoc;
}

/**
 * @Entity(repositoryClass="Entity\Repository\Agent")
 * @Table(name="agent")
 */
class DDC1587ValidEntity1
{
    /**
     * @var int
     *
     * @Id @GeneratedValue
     * @Column(name="pk", type="integer")
     */
    private $pk;

    /**
     * @var string
     *
     * @Column(name="name", type="string", length=32)
     */
    private $name;

    /**
     * @var Identifier
     *
     * @OneToOne(targetEntity="DDC1587ValidEntity2", cascade={"all"}, mappedBy="agent")
     * @JoinColumn(name="pk", referencedColumnName="pk_agent")
     */
    private $identifier;
}

/**
 * @Entity
 * @Table
 */
class DDC1587ValidEntity2
{
    /**
     * @var DDC1587ValidEntity1
     *
     * @Id
     * @OneToOne(targetEntity="DDC1587ValidEntity1", inversedBy="identifier")
     * @JoinColumn(name="pk_agent", referencedColumnName="pk", nullable=false)
     */
    private $agent;

    /**
     * @var string
     *
     * @Column(name="num", type="string", length=16, nullable=true)
     */
    private $num;
}

/**
 * @Entity
 */
class DDC1649One
{
    /**
     * @Id @Column @GeneratedValue
     */
    public $id;
}

/**
 * @Entity
 */
class DDC1649Two
{
    /** @Id @ManyToOne(targetEntity="DDC1649One")@JoinColumn(name="id", referencedColumnName="id")  */
    public $one;
}

/**
 * @Entity
 */
class DDC1649Three
{
    /** @Id @ManyToOne(targetEntity="DDC1649Two") @JoinColumn(name="id",
     * referencedColumnName="id") */
    private $two;
}

/**
 * @Entity
 */
class DDC3274One
{
    /**
     * @Id @Column @GeneratedValue
     */
    private $id;

    /**
     * @OneToMany(targetEntity="DDC3274Two", mappedBy="one")
     */
    private $two;
}

/**
 * @Entity
 */
class DDC3274Two
{
    /**
     * @Id
     * @ManyToOne(targetEntity="DDC3274One")
     */
    private $one;
}

/**
 * @Entity
 */
class DDC3322ValidEntity1
{
    /**
     * @Id @Column @GeneratedValue
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="DDC3322One", inversedBy="validAssoc")
     */
    private $oneValid;

    /**
     * @ManyToOne(targetEntity="DDC3322One", inversedBy="invalidAssoc")
     */
    private $oneInvalid;

    /**
     * @ManyToOne(targetEntity="DDC3322Two", inversedBy="validAssoc")
     */
    private $twoValid;

    /**
     * @ManyToOne(targetEntity="DDC3322Two", inversedBy="invalidAssoc")
     */
    private $twoInvalid;

    /**
     * @ManyToOne(targetEntity="DDC3322Three", inversedBy="validAssoc")
     */
    private $threeValid;

    /**
     * @ManyToOne(targetEntity="DDC3322Three", inversedBy="invalidAssoc")
     */
    private $threeInvalid;

    /**
     * @OneToMany(targetEntity="DDC3322ValidEntity2", mappedBy="manyToOne")
     */
    private $oneToMany;

    /**
     * @ManyToOne(targetEntity="DDC3322ValidEntity2", inversedBy="oneToMany")
     */
    private $manyToOne;

    /**
     * @OneToOne(targetEntity="DDC3322ValidEntity2", mappedBy="oneToOneOwning")
     */
    private $oneToOneInverse;

    /**
     * @OneToOne(targetEntity="DDC3322ValidEntity2", inversedBy="oneToOneInverse")
     */
    private $oneToOneOwning;
}

/**
 * @Entity
 */
class DDC3322ValidEntity2
{
    /**
     * @Id @Column @GeneratedValue
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="DDC3322ValidEntity1", inversedBy="oneToMany")
     */
    private $manyToOne;

    /**
     * @OneToMany(targetEntity="DDC3322ValidEntity1", mappedBy="manyToOne")
     */
    private $oneToMany;

    /**
     * @OneToOne(targetEntity="DDC3322ValidEntity1", inversedBy="oneToOneInverse")
     */
    private $oneToOneOwning;

    /**
     * @OneToOne(targetEntity="DDC3322ValidEntity1", mappedBy="oneToOneOwning")
     */
    private $oneToOneInverse;
}

/**
 * @Entity
 */
class DDC3322One
{
    /**
     * @Id @Column @GeneratedValue
     */
    private $id;

    /**
     * @OneToMany(targetEntity="DDC3322ValidEntity1", mappedBy="oneValid")
     * @OrderBy({"id" = "ASC"})
     */
    private $validAssoc;

    /**
     * @OneToMany(targetEntity="DDC3322ValidEntity1", mappedBy="oneInvalid")
     * @OrderBy({"invalidField" = "ASC"})
     */
    private $invalidAssoc;
}

/**
 * @Entity
 */
class DDC3322Two
{
    /**
     * @Id @Column @GeneratedValue
     */
    private $id;

    /**
     * @OneToMany(targetEntity="DDC3322ValidEntity1", mappedBy="twoValid")
     * @OrderBy({"manyToOne" = "ASC"})
     */
    private $validAssoc;

    /**
     * @OneToMany(targetEntity="DDC3322ValidEntity1", mappedBy="twoInvalid")
     * @OrderBy({"oneToMany" = "ASC"})
     */
    private $invalidAssoc;
}

/**
 * @Entity
 */
class DDC3322Three
{
    /**
     * @Id @Column @GeneratedValue
     */
    private $id;

    /**
     * @OneToMany(targetEntity="DDC3322ValidEntity1", mappedBy="threeValid")
     * @OrderBy({"oneToOneOwning" = "ASC"})
     */
    private $validAssoc;

    /**
     * @OneToMany(targetEntity="DDC3322ValidEntity1", mappedBy="threeInvalid")
     * @OrderBy({"oneToOneInverse" = "ASC"})
     */
    private $invalidAssoc;
}
