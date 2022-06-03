<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\Driver\StaticPHPDriver;
use Doctrine\Tests\Models\DDC889\DDC889Class;

use const DIRECTORY_SEPARATOR;

class StaticPHPMappingDriverTest extends MappingDriverTestCase
{
    protected function loadDriver(): MappingDriver
    {
        return new StaticPHPDriver(__DIR__ . DIRECTORY_SEPARATOR . 'php');
    }

    /**
     * All class with static::loadMetadata are entities for php driver
     *
     * @group DDC-889
     */
    public function testinvalidEntityOrMappedSuperClassShouldMentionParentClasses(): void
    {
        self::assertInstanceOf(ClassMetadata::class, $this->createClassMetadata(DDC889Class::class));
    }

    /**
     * @group DDC-2825
     * @group 881
     */
    public function testSchemaDefinitionViaExplicitTableSchemaAnnotationProperty(): void
    {
        self::markTestIncomplete();
    }

    /**
     * @group DDC-2825
     * @group 881
     */
    public function testSchemaDefinitionViaSchemaDefinedInTableNameInTableAnnotationProperty(): void
    {
        self::markTestIncomplete();
    }

    public function testEntityIncorrectIndexes(): void
    {
        self::markTestSkipped('Static PHP driver does not ensure index correctness');
    }

    public function testEntityIncorrectUniqueContraint(): void
    {
        self::markTestSkipped('Static PHP driver does not ensure index correctness');
    }
}
