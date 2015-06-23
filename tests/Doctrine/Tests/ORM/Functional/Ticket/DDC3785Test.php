<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class DDC3785Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        Type::addType('ddc3785_asset_id', __NAMESPACE__ . '\\DDC3785_AssetIdType');

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC3785_Asset'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC3785_AssetId'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC3785_Attribute')
            ));
        } catch(\Exception $e) {
        }
    }

    /**
     * @group DDC-3785
     */
    public function testOwningValueObjectIdIsCorrectlyTransformedWhenRemovingOrphanedChildEntities()
    {
    	$id = new DDC3785_AssetId("919609ba-57d9-4a13-be1d-d202521e858a");
    	$attributes = array(
    		$attribute1 = new DDC3785_Attribute("foo1", "bar1"), 
    		$attribute2 = new DDC3785_Attribute("foo2", "bar2")
    	);
        $this->_em->persist($asset = new DDC3785_Asset($id, $attributes));
        $this->_em->flush();

        $asset->getAttributes()->removeElement($attribute1);

        $this->_em->persist($asset);
        $this->_em->flush();
    }
}

/**
 * @Entity
 * @Table(name="asset")
 */
class DDC3785_Asset
{
    /**
     * @Id @GeneratedValue(strategy="NONE") @Column(type="ddc3785_asset_id")
     */
    private $id;

    /**
     * @ManyToMany(targetEntity="DDC3785_Attribute", cascade={"persist"}, orphanRemoval=true)
     * @JoinTable(name="asset_attributes",
     *      joinColumns={@JoinColumn(name="asset_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="attribute_id", referencedColumnName="id")}
     *      )
     **/
    private $attributes;

    public function __construct(DDC3785_AssetId $id, $attributes = array())
    {
    	$this->id = $id;
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
 * @Entity
 * @Table(name="attribute")
 */
class DDC3785_Attribute
{
	/**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
	private $id;

	/** @Column(type = "string") */
	private $name;

	/** @Column(type = "string") */
	private $value;

	public function __construct($name, $value)
	{
		$this->name = $name;
		$this->value = $value;
	}
}

/** @Embeddable */
class DDC3785_AssetId
{
    /** @Column(type = "guid") */
    private $id;

    public function __construct($id)
    {
    	$this->id = $id;
    }

    public function __toString()
    {
    	return $this->id;
    }
}

class DDC3785_AssetIdType extends Type
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
        return (string)$value;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return new DDC3785_AssetId($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'ddc3785_asset_id';
    }
}
