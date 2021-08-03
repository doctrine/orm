<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\Tests\Models\DDC3711\DDC3711EntityA;
use Doctrine\Tests\ORM\Mapping\YamlMappingDriverTest;

class DDC3711Test extends YamlMappingDriverTest
{
    public function testCompositeKeyForJoinTableInManyToManyCreation(): void
    {
        $yamlDriver = $this->loadDriver();

        $em = $this->getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($yamlDriver);
        $factory = new ClassMetadataFactory();
        $factory->setEntityManager($em);

        $entityA = new ClassMetadata(DDC3711EntityA::class);
        $entityA = $factory->getMetadataFor(DDC3711EntityA::class);

        self::assertEquals(['link_a_id1' => 'id1', 'link_a_id2' => 'id2'], $entityA->associationMappings['entityB']['relationToSourceKeyColumns']);
        self::assertEquals(['link_b_id1' => 'id1', 'link_b_id2' => 'id2'], $entityA->associationMappings['entityB']['relationToTargetKeyColumns']);
    }
}
