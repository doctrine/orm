<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

class DDC192Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function testSchemaCreation()
    {
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC192User'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC192Phonenumber')
        ));
    }
}


/**
 * @Entity @Table(name="ddc192_users")
 */
class DDC192User
{ 
    /**
     * @Id @Column(name="id", type="integer") 
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
    
    /**
     * @Column(name="name", type="string")
     */
    public $name;
}


/**
 * @Entity @Table(name="ddc192_phonenumbers")
 */
class DDC192Phonenumber
{
    /**
     * @Id @Column(name="phone", type="string", length=40)
     */
    protected $phone;
    
    /**
     * @Id @Column(name="userId", type="integer")
     */
    protected $userId;
    
    /**
     * @Id
     * @ManyToOne(targetEntity="DDC192User")
     * @JoinColumn(name="userId", referencedColumnName="id")
     */
    protected $User; // Id on this docblock is ignored!
    
    
    public function setPhone($value) { $this->phone = $value; }
    
    public function getPhone() { return $this->phone; }
    
    public function setUser(User $user) 
    {
        $this->User = $user;
        $this->userId = $user->getId(); // TODO: Remove once ManyToOne supports Id annotation
    }
    
    public function getUser() { return $this->User; }
}