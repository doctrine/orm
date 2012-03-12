<?php
namespace Doctrine\Tests\ORM\Functional;

/**
 * @group DDC-451
 */
class UUIDGeneratorTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        if ($this->_em->getConnection()->getDatabasePlatform()->getName() != 'mysql') {
            $this->markTestSkipped('Currently restricted to MySQL platform.');
        }

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\UUIDEntity')
        ));
    }

    public function testGenerateUUID()
    {
        $entity = new UUIDEntity();

        $this->_em->persist($entity);
        $this->assertNotNull($entity->getId());
        $this->assertTrue(strlen($entity->getId()) > 0);
    }
}

/**
 * @Entity
 */
class UUIDEntity
{
    /** @Id @Column(type="string") @GeneratedValue(strategy="UUID") */
    private $id;
    /**
     * Get id.
     *
     * @return id.
     */
    public function getId()
    {
        return $this->id;
    }
}
