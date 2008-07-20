<?php
require_once 'lib/DoctrineTestInit.php';
 
class Orm_Associations_OneToOneMappingTest extends Doctrine_OrmTestCase
{    
    public function testCorrectOneToOneBidirectionalMapping()
    {
        $owningSideMapping = array(
            'fieldName' => 'address',
            'targetEntity' => 'Address',
            'joinColumns' => array('address_id' => 'id'),
            'sourceEntity' => 'Person' // This is normally filled by ClassMetadata
        );
        
        $oneToOneMapping = new Doctrine_Association_OneToOne($owningSideMapping);
        
        $this->assertEquals(array('address_id' => 'id'), $oneToOneMapping->getSourceToTargetKeyColumns());
        $this->assertEquals(array('id' => 'address_id'), $oneToOneMapping->getTargetToSourceKeyColumns());
        $this->assertEquals('Address', $oneToOneMapping->getTargetEntityName());
        $this->assertEquals('Person', $oneToOneMapping->getSourceEntityName());
        $this->assertEquals('address', $oneToOneMapping->getSourceFieldName());
        $this->assertTrue($oneToOneMapping->isOwningSide());
        
        
        $inverseSideMapping = array(
            'mappedBy' => 'address'
        );
        
        $oneToOneMapping = new Doctrine_Association_OneToOne($inverseSideMapping);
        $this->assertEquals('address', $oneToOneMapping->getMappedByFieldName());
        $this->assertTrue($oneToOneMapping->isInverseSide());
        
    }
    
}
?>