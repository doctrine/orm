<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Schema\Sequence;
use Doctrine\Tests\OrmFunctionalTestCase;

class SequenceEmulatedIdentityStrategyTest extends OrmFunctionalTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        if ( ! $this->_em->getConnection()->getDatabasePlatform()->usesSequenceEmulatedIdentityColumns()) {
            $this->markTestSkipped(
                'This test is special to platforms emulating IDENTITY key generation strategy through sequences.'
            );
        } else {
            try {
                $this->_schemaTool->createSchema(
                    [$this->_em->getClassMetadata(SequenceEmulatedIdentityEntity::class)]
                );
            } catch (\Exception $e) {
                // Swallow all exceptions. We do not test the schema tool here.
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        $connection = $this->_em->getConnection();
        $platform   = $connection->getDatabasePlatform();

        // drop sequence manually due to dependency
        $connection->exec(
            $platform->getDropSequenceSQL(
                new Sequence($platform->getIdentitySequenceName('seq_identity', 'id'))
            )
        );
    }

    public function testPreSavePostSaveCallbacksAreInvoked()
    {
        $entity = new SequenceEmulatedIdentityEntity();
        $entity->setValue('hello');
        $this->_em->persist($entity);
        $this->_em->flush();
        $this->assertTrue(is_numeric($entity->getId()));
        $this->assertTrue($entity->getId() > 0);
        $this->assertTrue($this->_em->contains($entity));
    }
}

/** @Entity @Table(name="seq_identity") */
class SequenceEmulatedIdentityEntity
{
    /** @Id @Column(type="integer") @GeneratedValue(strategy="IDENTITY") */
    private $id;

    /** @Column(type="string") */
    private $value;

    public function getId()
    {
        return $this->id;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }
}
