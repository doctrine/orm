<?php

namespace Doctrine\Tests\ORM\Associations;

require_once __DIR__ . '/../../TestInit.php';
 
class OneToOneMappingTest extends \Doctrine\Tests\OrmTestCase
{    
    public function testCorrectOneToOneBidirectionalMapping()
    {
        $owningSideMapping = array(
            'fieldName' => 'address',
            'targetEntity' => 'Address',
            'joinColumns' => array(array('name' => 'address_id', 'referencedColumnName' => 'id')),
            'sourceEntity' => 'Person', // This is normally filled by ClassMetadata
        );
        
        $oneToOneMapping = new \Doctrine\ORM\Mapping\OneToOneMapping($owningSideMapping);
        
        $this->assertEquals(array('address_id' => 'id'), $oneToOneMapping->getSourceToTargetKeyColumns());
        $this->assertEquals(array('id' => 'address_id'), $oneToOneMapping->getTargetToSourceKeyColumns());
        $this->assertEquals('Address', $oneToOneMapping->getTargetEntityName());
        $this->assertEquals('Person', $oneToOneMapping->getSourceEntityName());
        $this->assertEquals('address', $oneToOneMapping->getSourceFieldName());
        $this->assertTrue($oneToOneMapping->isOwningSide());
        
        
        $inverseSideMapping = array(
            'fieldName' => 'person',
            'sourceEntity' => 'Address',
            'targetEntity' => 'Person',
            'mappedBy' => 'address'
        );
        
        $oneToOneMapping = new \Doctrine\ORM\Mapping\OneToOneMapping($inverseSideMapping);
        $this->assertEquals('address', $oneToOneMapping->getMappedByFieldName());
        $this->assertEquals('Address', $oneToOneMapping->getSourceEntityName());
        $this->assertEquals('Person', $oneToOneMapping->getTargetEntityName());
        $this->assertTrue($oneToOneMapping->isInverseSide());
        
    }
    
}
