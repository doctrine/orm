<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

use function uniqid;

/**
 * @group GH7877
 */
class GH7877Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            GH7877ApplicationGenerated::class,
            GH7877DatabaseGenerated::class
        );
    }

    public function testNoExtraUpdateWithApplicationGeneratedId(): void
    {
        $entity         = new GH7877ApplicationGenerated($entityId = uniqid());
        $entity->parent = $entity;
        $this->_em->persist($entity);

        if ($this->isQueryLogAvailable()) {
            $this->getQueryLog()->reset()->enable();
        }

        $this->_em->flush();
        if ($this->isQueryLogAvailable()) {
            if ($this->getQueryLog()->queries[0]['sql'] === '"START TRANSACTION"') {
                self::assertQueryCount(3);
            } else {
                self::assertQueryCount(1);
            }
        }

        $this->_em->clear();

        $child = $this->_em->find(GH7877ApplicationGenerated::class, $entityId);
        $this->assertSame($entityId, $child->parent->id);
    }

    public function textExtraUpdateWithDatabaseGeneratedId(): void
    {
        $entity         = new GH7877DatabaseGenerated();
        $entity->parent = $entity;
        $this->_em->persist($entity);

        if ($this->isQueryLogAvailable()) {
            $this->getQueryLog()->reset()->enable();
        }

        $this->_em->flush();
        if ($this->isQueryLogAvailable()) {
            if ($this->getQueryLog()->queries[0]['sql'] === '"START TRANSACTION"') {
                self::assertQueryCount(4);
            } else {
                self::assertQueryCount(2);
            }
        }

        $entityId = $entity->id;
        $this->_em->clear();

        $child = $this->_em->find(GH7877DatabaseGenerated::class, $entityId);
        $this->assertSame($entityId, $child->parent->id);
    }
}

/**
 * @ORM\Entity
 */
class GH7877ApplicationGenerated
{
    public function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     * @ORM\GeneratedValue(strategy="NONE")
     *
     * @var string
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="Doctrine\Tests\ORM\Functional\GH7877ApplicationGenerated")
     *
     * @var self
     */
    public $parent;
}

/**
 * @ORM\Entity
 */
class GH7877DatabaseGenerated
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="Doctrine\Tests\ORM\Functional\GH7877DatabaseGenerated")
     *
     * @var self
     */
    public $parent;
}
