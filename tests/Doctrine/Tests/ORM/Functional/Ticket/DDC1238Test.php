<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\Models\CMS\CmsEmployee;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1238
 */
class DDC1238Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1238User'),
                #$this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1238UserBase'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1238UserSuperClass'),
            ));
        } catch(\PDOException $e) {
            
        }
    }
    
    public function testIssue()
    {
        $user = new DDC1238User;
        $user->setName("test");
        
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();
        
        $userId = $user->getId();
        $this->_em->clear();
        
        $user = $this->_em->getReference(__NAMESPACE__ . '\\DDC1238User', $userId);
        $this->_em->clear();
        #$user2 = $this->_em->getReference(__NAMESPACE__ . '\\DDC1238User', $userId);
        
        xdebug_start_trace("/tmp/doctrine");
        $userId = $user->getId();
        
        $this->assertNotSame($user, $user2);
        $this->assertNull($userId, "This proxy is unitialized and was cleared from the identity map, so no loading possible.");
    }
}

/**
 * @MappedSuperclass
 */
abstract class DDC1238UserSuperClass
{
    /**
     * @Column
     * @var string
     */
    private $name;
    
    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}

/**
 * @Entity
 */
class DDC1238User extends DDC1238UserSuperClass
{
    /** @Id @GeneratedValue @Column(type="integer") */
    private $id;
    
    public function getId()
    {
        return $this->id;
    }
}

