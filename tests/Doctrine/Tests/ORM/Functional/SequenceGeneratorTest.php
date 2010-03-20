<?php

namespace Doctrine\Tests\ORM\Functional;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Description of SequenceGeneratorTest
 *
 * @author robo
 */
class SequenceGeneratorTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        if (!$this->_em->getConnection()->getDatabasePlatform()->supportsSequences()) {
            $this->markTestSkipped('Only working for Databases that support sequences.');
        }

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\SequenceEntity'),
            ));
        } catch(\Exception $e) {

        }
    }

    public function testHighAllocationSizeSequence()
    {
        for ($i = 0; $i < 11; $i++) {
            $e = new SequenceEntity();
            $this->_em->persist($e);
        }
        $this->_em->flush();
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
     * @SequenceGenerator(allocationSize=5,sequenceName="person_id_seq")
     */
    public $id;
}