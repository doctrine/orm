<?php

namespace Doctrine\Tests\ORM\Functional;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Description of SequenceGeneratorTest
 *
 * @author robo
 */
class SequenceGeneratorTest extends OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        if ( ! $this->_em->getConnection()->getDatabasePlatform()->supportsSequences()) {
            $this->markTestSkipped('Only working for Databases that support sequences.');
        }

        try {
            $this->_schemaTool->createSchema(
                [
                    $this->_em->getClassMetadata(SequenceEntity::class),
                ]
            );
        } catch(\Exception $e) {
        }
    }

    public function testHighAllocationSizeSequence()
    {
        for ($i = 0; $i < 11; ++$i) {
            $this->_em->persist(new SequenceEntity());
        }

        $this->_em->flush();

        self::assertCount(11, $this->_em->getRepository(SequenceEntity::class)->findAll());
    }
}

/**
 * @Entity
 */
class SequenceEntity
{
    /**
     * @Id
     * @column(type="integer")
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(allocationSize=5, sequenceName="person_id_seq")
     */
    public $id;
}
