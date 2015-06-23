<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Tools\Pagination\Paginator;

class DDC3789Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        Type::addType('ddc3789_asset_id', __NAMESPACE__ . '\\DDC3789_AssetIdType');

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC3789_Asset'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC3789_AssetId')
            ));
        } catch(\Exception $e) {
        }
    }

    /**
     * @group DDC-3789
     */
    public function testPaginatorCanHandleValueObjectIds()
    {
        $id1 = new DDC3789_AssetId("919609ba-57d9-4a13-be1d-d202521e858a");
        $id2 = new DDC3789_AssetId("919609ba-57d9-4a13-be1d-d202521e858b");
        $id3 = new DDC3789_AssetId("919609ba-57d9-4a13-be1d-d202521e858c");
        $id4 = new DDC3789_AssetId("919609ba-57d9-4a13-be1d-d202521e858d");

        $this->_em->persist(new DDC3789_Asset($id1, "red"));
        $this->_em->persist(new DDC3789_Asset($id2, "blue"));
        $this->_em->persist(new DDC3789_Asset($id3, "blue"));
        $this->_em->persist(new DDC3789_Asset($id4, "yellow"));
        $this->_em->flush();

        $qb = $this->_em->createQueryBuilder();
        $query = $qb
            ->select('a')
            ->from(DDC3789_Asset::CLASS, 'a')
            ->where('a.color = :color')
            ->setParameter('color', "blue")
            ->getQuery();

        $paginator = new Paginator($query);

        $this->assertCount(2, $paginator, "Paginator did not retrieve the correct number of results.");

        $assets = [];
        foreach ($paginator as $asset) {
        	$assets[] = $asset;
        }

        $this->assertCount(2, $assets, "Paginator did not retrieve the entities.");
    }
}

/**
 * @Entity
 * @Table(name="asset")
 */
class DDC3789_Asset
{
    /**
     * @Id @GeneratedValue(strategy="NONE") @Column(type="ddc3789_asset_id")
     */
    private $id;

    /** @Column(type = "string") */
    private $color;

    public function __construct(DDC3789_AssetId $id, $color)
    {
    	$this->id = $id;
    	$this->color = $color;
    }

    public function getId()
    {
        return $this->id;
    }
}

/** @Embeddable */
class DDC3789_AssetId
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

class DDC3789_AssetIdType extends Type
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
        return new DDC3789_AssetId($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'ddc3789_asset_id';
    }
}
