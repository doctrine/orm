<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

class DDC3933Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    protected function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC3933User'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC3933UserData'),
        ));
    }


    public function testMergeDetachedPrimaryForeignKey()
    {
        $user        = new DDC3933User(1, 'foo');
        $changedUser = new DDC3933User(1, 'bar');

        // Persist a duplicate user data to ensure that the subsequent merge()
        // will have a primary foreign key entity considered detached by the EM.
        $this->_em->persist($changedUser->getData());
        $this->_em->merge($user);

        // Get the user and check that the merge worked as expected.
        $retrievedUser = $this->_em->find(__NAMESPACE__ . '\DDC3933User', 1);
        $this->assertSame('foo', $retrievedUser->getName());
    }

}

/**
 * @Entity
 * @Table(name="DDC3933User")
 */
class DDC3933User
{

    /**
     * @Id
     * @Column(name="id", type="integer")
     */
    private $id;

    /**
     * @Column(name="name", type="string")
     */
    private $name;

    /**
     * @OneToOne(targetEntity="DDC3933UserData", mappedBy="user")
     */
    private $data;


    public function __construct($id, $name)
    {
        $this->id   = $id;
        $this->name = $name;
        $this->data = new DDC3933UserData($this);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getData()
    {
        return $this->data;
    }

}


/**
 * @Entity
 * @Table(name="DDC3933UserData")
 */
class DDC3933UserData
{

    /**
     * @Id
     * @OneToOne(targetEntity="DDC3933User", inversedBy="data")
     */
    private $user;


    public function __construct(DDC3933User $user)
    {
        $this->user = $user;
    }

}
