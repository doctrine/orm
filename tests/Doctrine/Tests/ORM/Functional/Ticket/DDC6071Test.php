<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\Tests\ORM\Mapping\YamlMappingDriverTest;

class DDC6071Test extends YamlMappingDriverTest
{
    
    /**
     * @group DDC-6071
     */
    public function testGenerateEntityWithSequenceGeneratorName()
    {
        
        $yamlDriver = $this->_loadDriver();

        $em = $this->_getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($yamlDriver);
        $factory = new ClassMetadataFactory();
        $factory->setEntityManager($em);

        $metadata = new ClassMetadata('Doctrine\Tests\Models\DDC60714\DDC6071User');
        $metadata = $factory->getMetadataFor('Doctrine\Tests\Models\DDC6071\DDC6071User');
        
        $this->assertArrayHasKey('sequenceName', $metadata->sequenceGeneratorDefinition);
        $this->assertContains('DDC6071_SQ', $metadata->sequenceGeneratorDefinition);
    }
    
    /**
     * @group DDC-6071
     */
    public function testGenerateEntityWithSequenceGeneratorCustomName()
    {
        
        $yamlDriver = $this->_loadDriver();

        $em = $this->_getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($yamlDriver);
        $factory = new ClassMetadataFactory();
        $factory->setEntityManager($em);

        $metadata = new ClassMetadata('Doctrine\Tests\Models\DDC60714\DDC6071User');
        $metadata = $factory->getMetadataFor('Doctrine\Tests\Models\DDC6071\DDC6071User');
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_SEQUENCE);
        $metadata->setCustomGeneratorDefinition(array(
            'sequenceName'      => 'DDC6071_SQ',
            'allocationSize'    => 1,
            'initialValue'      => 1
        ));
        
        $this->assertArrayHasKey('sequenceName', $metadata->customGeneratorDefinition);
        $this->assertContains('DDC6071_SQ', $metadata->customGeneratorDefinition);
    }
}