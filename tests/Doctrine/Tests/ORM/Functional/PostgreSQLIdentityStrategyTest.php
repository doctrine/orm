<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Event\PreUpdateEventArgs;

require_once __DIR__ . '/../../TestInit.php';

class PostgreSQLIdentityStrategyTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        parent::setUp();
        if ($this->_em->getConnection()->getDatabasePlatform()->getName() != 'postgresql') {
            $this->markTestSkipped('This test is special to the PostgreSQL IDENTITY key generation strategy.');
        } else {
            try {
                $this->_schemaTool->createSchema(array(
                        $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\PostgreSQLIdentityEntity'),
                ));
            } catch (\Exception $e) {
                // Swallow all exceptions. We do not test the schema tool here.
            }
        }
    }

    protected function tearDown() {
        parent::tearDown();
        // drop sequence manually due to dependency
        $this->_em->getConnection()->exec('DROP SEQUENCE postgresqlidentityentity_id_seq CASCADE');
    }
    
    public function testPreSavePostSaveCallbacksAreInvoked()
    {        
        $entity = new PostgreSQLIdentityEntity();
        $entity->setValue('hello');
        $this->_em->persist($entity);
        $this->_em->flush();
        $this->assertTrue(is_numeric($entity->getId()));
        $this->assertTrue($entity->getId() > 0);
        $this->assertTrue($this->_em->contains($entity));
    }
}

/** @Entity */
class PostgreSQLIdentityEntity {
    /** @Id @Column(type="integer") @GeneratedValue(strategy="IDENTITY") */
    private $id;
    /** @Column(type="string") */
    private $value;
    public function getId() {return $this->id;}
    public function getValue() {return $this->value;}
    public function setValue($value) {$this->value = $value;}
}
