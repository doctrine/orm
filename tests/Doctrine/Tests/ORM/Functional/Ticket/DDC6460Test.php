<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Proxy\Proxy;

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
        $isFieldMapped = $this->_em
            ->getClassMetadata(DDC6460Entity::class)
            ->hasField('embedded');

        $this->assertTrue($isFieldMapped);
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
        $this->_em->persist($entity);

        $second = new DDC6460ParentEntity();
        $second->id = 1;
        $second->lazyLoaded = $entity;
        $this->_em->persist($second);
        $this->_em->flush();

        $this->_em->clear();

        $secondEntityWithLazyParameter = $this->_em->getRepository(DDC6460ParentEntity::class)->findOneById(1);

        $this->assertInstanceOf(Proxy::class, $secondEntityWithLazyParameter->lazyLoaded);
        $this->assertInstanceOf(DDC6460Entity::class, $secondEntityWithLazyParameter->lazyLoaded);
        $this->assertFalse($secondEntityWithLazyParameter->lazyLoaded->__isInitialized());
        $this->assertEquals($secondEntityWithLazyParameter->lazyLoaded->embedded, $entity->embedded);
        $this->assertTrue($secondEntityWithLazyParameter->lazyLoaded->__isInitialized());
    }
}

/**
 * @Embeddable()
 */
class DDC6460Embeddable
{
    /** @Column(type="string") */
    public $field;
}

/**
 * @Entity()
 */
class DDC6460Entity
{
    /**
     * @Id
     * @GeneratedValue(strategy = "NONE")
     * @Column(type = "integer")
     */
    public $id;

    /** @Embedded(class = "DDC6460Embeddable") */
    public $embedded;
}

/**
 * @Entity()
 */
class DDC6460ParentEntity
{
    /**
     * @Id
     * @GeneratedValue(strategy = "NONE")
     * @Column(type = "integer")
     */
    public $id;

    /** @ManyToOne(targetEntity = "DDC6460Entity", fetch="EXTRA_LAZY", cascade={"persist"}) */
    public $lazyLoaded;
}
