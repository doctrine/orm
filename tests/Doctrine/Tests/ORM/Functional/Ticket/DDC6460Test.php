<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Proxy\Proxy;

/**
 * @group embedded
 */
class DDC6460Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        try {
            $this->setUpEntitySchema(
                [
                    DDC6460Entity::class,
                    DDC6460ParentEntity::class,
                ]
            );
        } catch (SchemaException $e) {
        }
    }

    /**
     * @group DDC-6460
     */
    public function testInlineEmbeddable()
    {
        $isFieldMapped = $this->em
            ->getClassMetadata(DDC6460Entity::class)
            ->hasField('embedded');

        self::assertTrue($isFieldMapped);
    }

    /**
     * @group DDC-6460
     */
    public function testInlineEmbeddableProxyInitialization()
    {
        $entity = new DDC6460Entity();
        $entity->id = 1;
        $entity->embedded = new DDC6460Embeddable();
        $entity->embedded->field = 'test';

        $this->em->persist($entity);

        $second = new DDC6460ParentEntity();
        $second->id = 1;
        $second->lazyLoaded = $entity;

        $this->em->persist($second);
        $this->em->flush();
        $this->em->clear();

        $secondEntityWithLazyParameter = $this->em->getRepository(DDC6460ParentEntity::class)->findOneById(1);

        self::assertInstanceOf(Proxy::class, $secondEntityWithLazyParameter->lazyLoaded);
        self::assertInstanceOf(DDC6460Entity::class, $secondEntityWithLazyParameter->lazyLoaded);
        self::assertFalse($secondEntityWithLazyParameter->lazyLoaded->__isInitialized());
        self::assertEquals($secondEntityWithLazyParameter->lazyLoaded->embedded, $entity->embedded);
        self::assertTrue($secondEntityWithLazyParameter->lazyLoaded->__isInitialized());
    }
}

/**
 * @ORM\Embeddable()
 */
class DDC6460Embeddable
{
    /** @ORM\Column(type="string") */
    public $field;
}

/**
 * @ORM\Entity()
 */
class DDC6460Entity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy = "NONE")
     * @ORM\Column(type = "integer")
     */
    public $id;

    /** @ORM\Embedded(class = "DDC6460Embeddable") */
    public $embedded;
}

/**
 * @ORM\Entity()
 */
class DDC6460ParentEntity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy = "NONE")
     * @ORM\Column(type = "integer")
     */
    public $id;

    /** @ORM\ManyToOne(targetEntity = "DDC6460Entity", fetch="EXTRA_LAZY", cascade={"persist"}) */
    public $lazyLoaded;
}
