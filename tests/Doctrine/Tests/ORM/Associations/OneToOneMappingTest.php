<?php

namespace Doctrine\Tests\ORM\Associations;

require_once __DIR__ . '/../../TestInit.php';

use Doctrine\ORM\Mapping\ClassMetadata;

class OneToOneMappingTest extends \Doctrine\Tests\OrmTestCase
{
    public function testCorrectOneToOneBidirectionalMapping()
    {
        $oneToOneMapping = array(
            'type' => ClassMetadata::ONE_TO_ONE,
            'fieldName' => 'address',
            'targetEntity' => 'Address',
            'joinColumns' => array(array('name' => 'address_id', 'referencedColumnName' => 'id')),
            'sourceEntity' => 'Person', // This is normally filled by ClassMetadata
        );
        
        $this->assertEquals(array('address_id' => 'id'), $oneToOneMapping->sourceToTargetKeyColumns);
        $this->assertEquals(array('id' => 'address_id'), $oneToOneMapping->targetToSourceKeyColumns);
        $this->assertEquals('Address', $oneToOneMapping->targetEntityName);
        $this->assertEquals('Person', $oneToOneMapping->sourceEntityName);
        $this->assertEquals('address', $oneToOneMapping->sourceFieldName);
        $this->assertTrue($oneToOneMapping->isOwningSide);

        $oneToOneMapping = array(
            'type' => ClassMetadata::ONE_TO_ONE,
            'fieldName' => 'person',
            'sourceEntity' => 'Address',
            'targetEntity' => 'Person',
            'mappedBy' => 'address'
        );
        
        $this->assertEquals('address', $oneToOneMapping->mappedBy);
        $this->assertEquals('Address', $oneToOneMapping->sourceEntityName);
        $this->assertEquals('Person', $oneToOneMapping->targetEntityName);
        $this->assertTrue( ! $oneToOneMapping->isOwningSide);
    }
}