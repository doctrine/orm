<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;

/**
 * Description of SequenceGeneratorTest
 */
class SequenceGeneratorTest extends OrmFunctionalTestCase
{
    public function setUp() : void
    {
        parent::setUp();

        if (! $this->em->getConnection()->getDatabasePlatform()->supportsSequences()) {
            $this->markTestSkipped('Only working for Databases that support sequences.');
        }

        try {
            $this->schemaTool->createSchema(
                [
                    $this->em->getClassMetadata(SequenceEntity::class),
                ]
            );
        } catch (Exception $e) {
        }
    }

    public function testHighAllocationSizeSequence() : void
    {
        for ($i = 0; $i < 11; ++$i) {
            $this->em->persist(new SequenceEntity());
        }

        $this->em->flush();

        self::assertCount(11, $this->em->getRepository(SequenceEntity::class)->findAll());
    }
}

/**
 * @ORM\Entity
 */
class SequenceEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(allocationSize=5,sequenceName="person_id_seq")
     */
    public $id;
}
