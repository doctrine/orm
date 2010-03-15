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
        
        $this->assertEquals(array('address_id' => 'id'), $oneToOneMapping->sourceToTargetKeyColumns);
        $this->assertEquals(array('id' => 'address_id'), $oneToOneMapping->targetToSourceKeyColumns);
        $this->assertEquals('Address', $oneToOneMapping->targetEntityName);
        $this->assertEquals('Person', $oneToOneMapping->sourceEntityName);
        $this->assertEquals('address', $oneToOneMapping->sourceFieldName);
        $this->assertTrue($oneToOneMapping->isOwningSide);

        $inverseSideMapping = array(
            'fieldName' => 'person',
            'sourceEntity' => 'Address',
            'targetEntity' => 'Person',
            'mappedBy' => 'address'
        );
        
        $oneToOneMapping = new \Doctrine\ORM\Mapping\OneToOneMapping($inverseSideMapping);
        $this->assertEquals('address', $oneToOneMapping->mappedBy);
        $this->assertEquals('Address', $oneToOneMapping->sourceEntityName);
        $this->assertEquals('Person', $oneToOneMapping->targetEntityName);
        $this->assertTrue($oneToOneMapping->isInverseSide());
    }
}