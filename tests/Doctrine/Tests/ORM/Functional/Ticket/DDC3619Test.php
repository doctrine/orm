<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

class DDC3619Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setup()
    {
        parent::setup();

        $this->_schemaTool->createSchema(
            array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC3619Entity'),
            )
        );
    }

    public function testIssue()
    {
        $uow = $this->_em->getUnitOfWork();

        $entity = new DDC3619Entity();
        $this->_em->persist($entity);
        $this->_em->flush();
        $this->assertTrue($uow->isInIdentityMap($entity));

        $this->_em->remove($entity);
        $this->assertFalse($uow->isInIdentityMap($entity));

        $this->_em->persist($entity);
        $this->assertTrue($uow->isInIdentityMap($entity));
    }
}

/**
 * @Entity()
 */
class DDC3619Entity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;
}
