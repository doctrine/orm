<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Annotation as ORM;

/**
 * @group DDC-2579
 */
class DDC2579Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        Type::addType(DDC2579Type::NAME, DDC2579Type::class);

        $this->schemaTool->createSchema(
            [
            $this->em->getClassMetadata(DDC2579Entity::class),
            $this->em->getClassMetadata(DDC2579EntityAssoc::class),
            $this->em->getClassMetadata(DDC2579AssocAssoc::class),
            ]
        );
    }

    public function testIssue()
    {
        $id         = new DDC2579Id("foo");
        $assoc      = new DDC2579AssocAssoc($id);
        $assocAssoc = new DDC2579EntityAssoc($assoc);
        $entity     = new DDC2579Entity($assocAssoc);
        $repository = $this->em->getRepository(DDC2579Entity::class);

        $this->em->persist($assoc);
        $this->em->persist($assocAssoc);
        $this->em->persist($entity);
        $this->em->flush();

        $entity->value++;

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $id       = $entity->id;
        $value    = $entity->value;
        $criteria = ['assoc' => $assoc, 'id' => $id];
        $entity   = $repository->findOneBy($criteria);

        self::assertInstanceOf(DDC2579Entity::class, $entity);
        self::assertEquals($value, $entity->value);

        $this->em->remove($entity);
        $this->em->flush();
        $this->em->clear();

        self::assertNull($repository->findOneBy($criteria));
        self::assertCount(0, $repository->findAll());
    }
}

/**
 * @ORM\Entity
 */
class DDC2579Entity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="ddc2579")
     */
    public $id;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="DDC2579EntityAssoc")
     * @ORM\JoinColumn(name="relation_id", referencedColumnName="association_id")
     */
    public $assoc;

    /**
     * @ORM\Column(type="integer")
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
 * @ORM\Entity
 */
class DDC2579EntityAssoc
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="DDC2579AssocAssoc")
     * @ORM\JoinColumn(name="association_id", referencedColumnName="associationId")
     */
    public $assocAssoc;

    public function __construct(DDC2579AssocAssoc $assocAssoc)
    {
        $this->assocAssoc = $assocAssoc;
    }
}

/**
 * @ORM\Entity
 */
class DDC2579AssocAssoc
{
    /**
     * @ORM\Id
     * @ORM\Column(type="ddc2579")
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
