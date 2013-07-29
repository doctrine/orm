<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * @group DDC-2579
 */
class DDC2579Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        Type::addType(DDC2579Type::NAME, DDC2579Type::CLASSNAME);

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(DDC2579Entity::CLASSNAME),
            $this->_em->getClassMetadata(DDC2579EntityAssoc::CLASSNAME),
            $this->_em->getClassMetadata(DDC2579AssocAssoc::CLASSNAME),
        ));
    }

    public function testIssue()
    {
        $id         = new DDC2579Id("foo");
        $assoc      = new DDC2579AssocAssoc($id);
        $assocAssoc = new DDC2579EntityAssoc($assoc);
        $entity     = new DDC2579Entity($assocAssoc);
        $repository = $this->_em->getRepository(DDC2579Entity::CLASSNAME);

        $this->_em->persist($assoc);
        $this->_em->persist($assocAssoc);
        $this->_em->persist($entity);
        $this->_em->flush();
        
        $entity->value++;

        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->clear();

        $id       = $entity->id;
        $value    = $entity->value;
        $criteria = array('assoc' => $assoc, 'id' => $id);
        $entity   = $repository->findOneBy($criteria);

        $this->assertInstanceOf(DDC2579Entity::CLASSNAME, $entity);
        $this->assertEquals($value, $entity->value);

        $this->_em->remove($entity);
        $this->_em->flush();
        $this->_em->clear();

        $this->assertNull($repository->findOneBy($criteria));
        $this->assertCount(0, $repository->findAll());
    }
}

/**
 * @Entity
 */
class DDC2579Entity
{

    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @Column(type="ddc2579")
     */
    public $id;

    /**
     * @Id
     * @ManyToOne(targetEntity="DDC2579EntityAssoc")
     * @JoinColumn(name="relation_id", referencedColumnName="association_id")
     */
    public $assoc;

    /**
     * @Column(type="integer")
     */
    public $value;

    public function __construct(DDC2579EntityAssoc $assoc, $value = 0)
    {
        $this->id    = $assoc->assocAssoc->associationId;
        $this->assoc = $assoc;
        $this->value = $value;
    }

}

/**
 * @Entity
 */
class DDC2579EntityAssoc
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @ManyToOne(targetEntity="DDC2579AssocAssoc")
     * @JoinColumn(name="association_id", referencedColumnName="associationId")
     */
    public $assocAssoc;

    public function __construct(DDC2579AssocAssoc $assocAssoc)
    {
        $this->assocAssoc = $assocAssoc;
    }
}

/**
 * @Entity
 */
class DDC2579AssocAssoc
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @Column(type="ddc2579")
     */
    public $associationId;

    public function __construct(DDC2579Id $id)
    {
        $this->associationId  = $id;
    }
}


class DDC2579Type extends StringType
{
    const NAME = 'ddc2579';
    const CLASSNAME = __CLASS__;

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return (string)$value;
    }

    public function convertToPhpValue($value, AbstractPlatform $platform)
    {
        return new DDC2579Id($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}

class DDC2579Id
{
    const CLASSNAME = __CLASS__;

    private $val;

    public function __construct($val)
    {
        $this->val = $val;
    }

    public function __toString()
    {
        return $this->val;
    }
}
