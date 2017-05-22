<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\ManyToOne;

class DDC6460Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(
                [
                    $this->_em->getClassMetadata(DDC6460Entity::class),
                    $this->_em->getClassMetadata(DDC6460ParentEntity::class),
                ]
            );
        } catch (\Exception $e) {
        }
    }

    public function testInlineEmbeddable()
    {
        $isFieldMapped = $this->_em
            ->getClassMetadata(DDC6460Entity::class)
            ->hasField('embedded');

        $this->assertTrue($isFieldMapped);
    }

    public function testInlineEmbeddableProxyInitialization()
    {
        $entity = new DDC6460Entity();
        $entity->id = 1;
        $entity->embedded = new DDC6460Embeddable();
        $entity->embedded->field = 'test';
        $this->_em->persist($entity);
        $this->_em->flush();

        $second = new DDC6460ParentEntity();
        $second->id = 1;
        $second->lazyLoaded = $entity;
        $this->_em->persist($second);
        $this->_em->flush();

        $this->_em->clear();

        $proxy = $this->_em->getRepository(DDC6460ParentEntity::class)->findOneById(1);

        $this->assertNotNull($proxy->lazyLoaded->embedded);
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

    /** @ManyToOne(targetEntity = "DDC6460Entity", fetch="EXTRA_LAZY") */
    public $lazyLoaded;
}
