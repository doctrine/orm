<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;


use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\Models\DDC3711\DDC3711EntityA;
use Doctrine\Tests\ORM\Mapping\YamlMappingDriverTest;
use Doctrine\Tests\VerifyDeprecations;

/**
 * @author Marc Pantel <pantel.m@gmail.com>
 */
class DDC3711Test extends YamlMappingDriverTest
{
    use VerifyDeprecations;

    public function testCompositeKeyForJoinTableInManyToManyCreation()
    {
        $yamlDriver = $this->_loadDriver();

        $em = $this->_getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($yamlDriver);
        $factory = new \Doctrine\ORM\Mapping\ClassMetadataFactory();
        $factory->setEntityManager($em);

        $entityA = new ClassMetadata(DDC3711EntityA::class);
        $entityA = $factory->getMetadataFor(DDC3711EntityA::class);

        $this->assertEquals(['link_a_id1' => "id1", 'link_a_id2' => "id2"], $entityA->associationMappings['entityB']['relationToSourceKeyColumns']);
        $this->assertEquals(['link_b_id1' => "id1", 'link_b_id2' => "id2"], $entityA->associationMappings['entityB']['relationToTargetKeyColumns']);
        $this->assertHasDeprecationMessages();
    }
}
