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
        
        for ($i = 0; $i < 5; $i++) {
            $user = $this->_em->getReference(__NAMESPACE__ . '\\DDC1238User', $user->getId());
        }
        
        $this->assertInstanceOf(__NAMESPACE__ . '\\DDC1238User', $user);
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
 * nothing
 */
abstract class DDC1238UserBase extends DDC1238UserSuperClass
{
    
}

/**
 * @Entity
 */
class DDC1238User extends DDC1238UserBase
{
    /** @Id @GeneratedValue @Column(type="integer") */
    private $id;
    
    public function getId()
    {
        return $this->id;
    }
}

