<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;
use Doctrine\ORM\UnitOfWork;

require_once __DIR__ . '/../../../TestInit.php';

class DDC381Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC381Entity'),
            ));
        } catch(\Exception $e) {

        }
    }

    public function testCallUnserializedProxyMethods()
    {
        $entity = new DDC381Entity();

        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->clear();
        $persistedId = $entity->getId();

        $entity = $this->_em->getReference('Doctrine\Tests\ORM\Functional\Ticket\DDC381Entity', $persistedId);

        // explicitly load proxy (getId() does not trigger reload of proxy)
        $id = $entity->getOtherMethod();

        $data = serialize($entity);
        $entity = unserialize($data);

        $this->assertEquals($persistedId, $entity->getId());
    }
}

/**
 * @Entity
 */
class DDC381Entity
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     */
    protected $id;

    public function getId()
    {
        return $this->id;
    }

    public function getOtherMethod()
    {

    }
}