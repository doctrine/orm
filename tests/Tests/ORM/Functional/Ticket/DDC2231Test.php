<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectManagerAware;
use Doctrine\Tests\OrmFunctionalTestCase;

use function get_class;
use function interface_exists;

/** @group DDC-2231 */
class DDC2231Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        if (! interface_exists(ObjectManagerAware::class)) {
            self::markTestSkipped('This test requires doctrine/persistence 2');
        }

        parent::setUp();

        $this->createSchemaForModels(DDC2231EntityY::class);
    }

    public function testInjectObjectManagerInProxyIfInitializedInUow(): void
    {
        $y1 = new DDC2231EntityY();

        $this->_em->persist($y1);

        $this->_em->flush();
        $this->_em->clear();

        $y1ref = $this->_em->getReference(get_class($y1), $y1->id);

        self::assertTrue($this->isUninitializedObject($y1ref));

        $id = $y1ref->doSomething();

        self::assertFalse($this->isUninitializedObject($y1ref));
        self::assertEquals($this->_em, $y1ref->om);
    }
}

if (interface_exists(ObjectManagerAware::class)) {
    /**
     * @Entity
     * @Table(name="ddc2231_y")
     */
    class DDC2231EntityY implements ObjectManagerAware
    {
        /**
         * @var int
         * @Id
         * @Column(type="integer")
         * @GeneratedValue
         */
        public $id;

        /** @var ObjectManager */
        public $om;

        public function injectObjectManager(ObjectManager $objectManager, ClassMetadata $classMetadata): void
        {
            $this->om = $objectManager;
        }

        public function getId(): int
        {
            return $this->id;
        }

        public function doSomething(): void
        {
        }
    }
}
