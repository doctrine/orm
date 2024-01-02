<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmTestCase;
use stdClass;

class FieldBuilderTest extends OrmTestCase
{
    public function testCustomIdGeneratorCanBeSet(): void
    {
        $cmBuilder = new ClassMetadataBuilder(new ClassMetadata(CmsUser::class));

        $fieldBuilder = $cmBuilder->createField('aField', 'string');

        $fieldBuilder->generatedValue('CUSTOM');
        $fieldBuilder->setCustomIdGenerator(stdClass::class);

        $fieldBuilder->build();

        self::assertEquals(ClassMetadata::GENERATOR_TYPE_CUSTOM, $cmBuilder->getClassMetadata()->generatorType);
        self::assertEquals(['class' => stdClass::class], $cmBuilder->getClassMetadata()->customGeneratorDefinition);
    }
}
