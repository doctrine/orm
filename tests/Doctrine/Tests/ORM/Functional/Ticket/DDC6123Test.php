<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

/**
 */
class DDC6123Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        parent::setUp();
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\MyUserEntity')
        ));

    }

    public function testCreateRetrieveUpdateDelete()
    {
        $user           = new MyUserEntity();
        $user->name     = 'MarioRossi';
        $this->_em->persist($user);

        $this->_em->flush();

        $retrievedUser = $this->_em->getRepository(get_class($user))->findOneById($user->id);
        $this->assertEquals($user ,$retrievedUser);
        $this->assertEquals(\Doctrine\ORM\UnitOfWork::STATE_MANAGED ,$this->_em->getUnitOfWork()->getEntityState($retrievedUser));

        $this->_em->remove($retrievedUser);

        $retrievedUserAfterRemove = $this->_em->getRepository(get_class($user))->findOneById($retrievedUser->id);
        $this->assertEquals(\Doctrine\ORM\UnitOfWork::STATE_REMOVED ,$this->_em->getUnitOfWork()->getEntityState($retrievedUserAfterRemove));
    }

}


/**
 * @Entity
 * @Table(name="`MyUser`")
 */
class MyUserEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @Column(type="string")
     */
    public $name;
}