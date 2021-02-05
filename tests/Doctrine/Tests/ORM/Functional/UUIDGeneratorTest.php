<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\OrmFunctionalTestCase;

use function strlen;

/**
 * @group DDC-451
 */
class UUIDGeneratorTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->_em->getConnection()->getDatabasePlatform()->getName() !== 'mysql') {
            $this->markTestSkipped('Currently restricted to MySQL platform.');
        }

        $this->_schemaTool->createSchema(
            [
                $this->_em->getClassMetadata(UUIDEntity::class),
            ]
        );
    }

    public function testGenerateUUID(): void
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
    public function getId(): id
    {
        return $this->id;
    }
}
