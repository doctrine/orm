<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;

class DDC3785Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        Type::addType('ddc3785_asset_id', DDC3785AssetIdType::class);

        try {
            $this->schemaTool->createSchema(
                [
                    $this->em->getClassMetadata(DDC3785Asset::class),
                    $this->em->getClassMetadata(DDC3785AssetId::class),
                    $this->em->getClassMetadata(DDC3785Attribute::class),
                ]
            );
        } catch (Exception $e) {
        }
    }

    /**
     * @group embedded
     * @group DDC-3785
     */
    public function testOwningValueObjectIdIsCorrectlyTransformedWhenRemovingOrphanedChildEntities() : void
    {
        $id = new DDC3785AssetId('919609ba-57d9-4a13-be1d-d202521e858a');

        $attributes = [
            $attribute1 = new DDC3785Attribute('foo1', 'bar1'),
            $attribute2 = new DDC3785Attribute('foo2', 'bar2'),
        ];

        $this->em->persist($asset = new DDC3785Asset($id, $attributes));
        $this->em->flush();

        $asset->getAttributes()
              ->removeElement($attribute1);

        $idToBeRemoved = $attribute1->id;

        $this->em->persist($asset);
        $this->em->flush();

        self::assertNull($this->em->find(DDC3785Attribute::class, $idToBeRemoved));
        self::assertNotNull($this->em->find(DDC3785Attribute::class, $attribute2->id));
    }
}

/**
 * @ORM\Entity
 * @ORM\Table(name="asset")
 */
class DDC3785Asset
{
    /** @ORM\Id @ORM\GeneratedValue(strategy="NONE") @ORM\Column(type="ddc3785_asset_id") */
    private $id;

    /**
     * @ORM\ManyToMany(targetEntity=DDC3785Attribute::class, cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinTable(
     *     name="asset_attributes",
     *     joinColumns={@ORM\JoinColumn(name="asset_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="attribute_id", referencedColumnName="id")}
     * )
     */
    private $attributes;

    public function __construct(DDC3785AssetId $id, $attributes = [])
    {
        $this->id         = $id;
        $this->attributes = new ArrayCollection();

        foreach ($attributes as $attribute) {
            $this->attributes->add($attribute);
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }
}

/**
 * @ORM\Entity
 * @ORM\Table(name="attribute")
 */
class DDC3785Attribute
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;

    /** @ORM\Column(type = "string") */
    private $name;

    /** @ORM\Column(type = "string") */
    private $value;

    public function __construct($name, $value)
    {
        $this->name  = $name;
        $this->value = $value;
    }
}

/** @ORM\Embeddable */
class DDC3785AssetId
{
    /** @ORM\Column(type = "guid") */
    private $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function __toString()
    {
        return (string) $this->id;
    }
}

class DDC3785AssetIdType extends Type
{
    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getGuidTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return (string) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return new DDC3785AssetId($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'ddc3785_asset_id';
    }
}
