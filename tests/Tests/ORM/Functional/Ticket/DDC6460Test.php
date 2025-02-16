<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use Doctrine\ORM\Mapping\Embedded;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

class DDC6460Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->setUpEntitySchema(
                [
                    DDC6460Entity::class,
                    DDC6460ParentEntity::class,
                ],
            );
        } catch (SchemaException) {
        }
    }

    #[Group('DDC-6460')]
    public function testInlineEmbeddable(): void
    {
        $isFieldMapped = $this->_em
            ->getClassMetadata(DDC6460Entity::class)
            ->hasField('embedded');

        self::assertTrue($isFieldMapped);
    }

    #[Group('DDC-6460')]
    public function testInlineEmbeddableProxyInitialization(): void
    {
        $entity                  = new DDC6460Entity();
        $entity->id              = 1;
        $entity->embedded        = new DDC6460Embeddable();
        $entity->embedded->field = 'test';
        $this->_em->persist($entity);

        $second             = new DDC6460ParentEntity();
        $second->id         = 1;
        $second->lazyLoaded = $entity;
        $this->_em->persist($second);
        $this->_em->flush();

        $this->_em->clear();

        $secondEntityWithLazyParameter = $this->_em->getRepository(DDC6460ParentEntity::class)->findOneById(1);

        self::assertInstanceOf(DDC6460Entity::class, $secondEntityWithLazyParameter->lazyLoaded);
        self::assertTrue($this->isUninitializedObject($secondEntityWithLazyParameter->lazyLoaded));
        self::assertEquals($secondEntityWithLazyParameter->lazyLoaded->embedded, $entity->embedded);
        self::assertFalse($this->isUninitializedObject($secondEntityWithLazyParameter->lazyLoaded));
    }
}

#[Embeddable]
class DDC6460Embeddable
{
    /** @var string */
    #[Column(type: 'string', length: 255)]
    public $field;
}

#[Entity]
class DDC6460Entity
{
    /** @var int */
    #[Id]
    #[GeneratedValue(strategy: 'NONE')]
    #[Column(type: 'integer')]
    public $id;

    /** @var DDC6460Embeddable */
    #[Embedded(class: 'DDC6460Embeddable')]
    public $embedded;
}

#[Entity]
class DDC6460ParentEntity
{
    /** @var int */
    #[Id]
    #[GeneratedValue(strategy: 'NONE')]
    #[Column(type: 'integer')]
    public $id;

    /** @var DDC6460Entity */
    #[ManyToOne(targetEntity: 'DDC6460Entity', fetch: 'EXTRA_LAZY', cascade: ['persist'])]
    public $lazyLoaded;
}
