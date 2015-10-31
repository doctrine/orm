<?php
/**
 * @author Marc Pantel <pantel.m@gmail.com>
 */

namespace Doctrine\Tests\ORM\Functional\Ticket;


use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\ORM\Mapping\YamlMappingDriverTest;

class DDC3711Test extends YamlMappingDriverTest
{
    public function testCompositeKeyForJoinTableInManyToManyCreation()
    {
        $yamlDriver = $this->_loadDriver();

        $em = $this->_getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($yamlDriver);
        $factory = new \Doctrine\ORM\Mapping\ClassMetadataFactory();
        $factory->setEntityManager($em);

        $entityA = new ClassMetadata('Doctrine\Tests\Models\DDC3711\DDC3711EntityA');
        $entityA = $factory->getMetadataFor('Doctrine\Tests\Models\DDC3711\DDC3711EntityA');

        $this->assertEquals(array('link_a_id1' => "id1", 'link_a_id2' => "id2"), $entityA->associationMappings['entityB']['relationToSourceKeyColumns']);
        $this->assertEquals(array('link_b_id1' => "id1", 'link_b_id2' => "id2"), $entityA->associationMappings['entityB']['relationToTargetKeyColumns']);

    }
}