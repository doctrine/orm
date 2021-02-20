<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\OrmFunctionalTestCase;

class JoinedTableWithPropertyAsDiscriminatorColumnTest extends OrmFunctionalTestCase
{
    private function dropSchema(): void
    {
        $this->_schemaTool->dropSchema(
            [
                $this->_em->getClassMetadata(JoinedTableWithPropertyAsDiscriminatorColumnRoot::class),
                $this->_em->getClassMetadata(JoinedTableWithPropertyAsDiscriminatorColumnChild::class),
                $this->_em->getClassMetadata(JoinedTableWithPropertyAsDiscriminatorColumnChild2::class),
            ]
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $this->dropSchema();
        $this->_schemaTool->createSchema(
            [
                $this->_em->getClassMetadata(JoinedTableWithPropertyAsDiscriminatorColumnRoot::class),
                $this->_em->getClassMetadata(JoinedTableWithPropertyAsDiscriminatorColumnChild::class),
                $this->_em->getClassMetadata(JoinedTableWithPropertyAsDiscriminatorColumnChild2::class),
            ]
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->dropSchema();
    }

    public function testIfQueryReturnsCorrectInstance(): void
    {
        $child       = new JoinedTableWithPropertyAsDiscriminatorColumnChild();
        $child->type = 'child2';

        $this->_em->persist($child);
        $this->_em->flush();
        $this->_em->clear();

        $q = $this->_em->createQuery('SELECT o FROM ' . JoinedTableWithPropertyAsDiscriminatorColumnRoot::class . ' o WHERE o.id = :id');
        $q->setParameter('id', $child->id);
        $object = $q->getSingleResult();

        $this->assertInstanceOf(JoinedTableWithPropertyAsDiscriminatorColumnChild::class, $object);
    }

    public function testIfRepositoryReturnsCorrectInstance(): void
    {
        $child       = new JoinedTableWithPropertyAsDiscriminatorColumnChild();
        $child->type = 'child2';

        $this->_em->persist($child);
        $this->_em->flush();
        $this->_em->clear();

        $object = $this->_em->getRepository(JoinedTableWithPropertyAsDiscriminatorColumnRoot::class)->find($child->id);
        $this->assertInstanceOf(JoinedTableWithPropertyAsDiscriminatorColumnChild::class, $object);
    }
}


/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({
 *     "child" = "JoinedTableWithPropertyAsDiscriminatorColumnChild",
 *     "child2" = "JoinedTableWithPropertyAsDiscriminatorColumnChild2",
 * })
 */
abstract class JoinedTableWithPropertyAsDiscriminatorColumnRoot
{
    /**
     * @var int|null
     * @Column(type="integer")
     * @Id
     * @GeneratedValue
     */
    public $id;
}

/**
 * @Entity
 */
class JoinedTableWithPropertyAsDiscriminatorColumnChild extends JoinedTableWithPropertyAsDiscriminatorColumnRoot
{
    /**
     * @var string|null
     * @Column(type="string", name="type_not_discriminator")
     */
    public $type;
}


/**
 * @Entity
 */
class JoinedTableWithPropertyAsDiscriminatorColumnChild2 extends JoinedTableWithPropertyAsDiscriminatorColumnRoot
{
}
