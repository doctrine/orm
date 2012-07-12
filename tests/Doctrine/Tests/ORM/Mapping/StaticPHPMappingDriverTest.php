<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\Common\Persistence\Mapping\Driver\StaticPHPDriver,
    Doctrine\ORM\Tools\Export\ClassMetadataExporter;

require_once __DIR__ . '/../../TestInit.php';

class StaticPHPMappingDriverTest extends AbstractMappingDriverTest
{
    protected function _loadDriver()
    {
        return new StaticPHPDriver(__DIR__ . DIRECTORY_SEPARATOR . 'php');
    }


    /**
     * All class with static::loadMetadata are entities for php driver
     *
     * @group DDC-889
     */
    public function testinvalidEntityOrMappedSuperClassShouldMentionParentClasses()
    {
        $this->createClassMetadata('Doctrine\Tests\Models\DDC889\DDC889Class');
    }
}