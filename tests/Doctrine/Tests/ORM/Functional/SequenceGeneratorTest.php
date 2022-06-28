<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Description of SequenceGeneratorTest
 */
class SequenceGeneratorTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->_em->getConnection()->getDatabasePlatform()->supportsSequences()) {
            self::markTestSkipped('Only working for Databases that support sequences.');
        }

        $this->createSchemaForModels(SequenceEntity::class);
    }

    public function testHighAllocationSizeSequence(): void
    {
        for ($i = 0; $i < 11; ++$i) {
            $this->_em->persist(new SequenceEntity());
        }

        $this->_em->flush();

        self::assertCount(11, $this->_em->getRepository(SequenceEntity::class)->findAll());
    }
}

/** @Entity */
class SequenceEntity
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(allocationSize=5, sequenceName="person_id_seq")
     */
    public $id;
}
