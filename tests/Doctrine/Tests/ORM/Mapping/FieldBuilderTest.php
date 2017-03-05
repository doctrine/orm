<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmTestCase;

class FieldBuilderTest extends OrmTestCase
{
    public function testCustomIdGeneratorCanBeSet()
    {
        $cmBuilder = new ClassMetadataBuilder(new ClassMetadataInfo(CmsUser::class));

        $fieldBuilder = $cmBuilder->createField('aField', 'string');

        $fieldBuilder->generatedValue('CUSTOM');
        $fieldBuilder->setCustomIdGenerator('stdClass');

        $fieldBuilder->build();

        $this->assertEquals(ClassMetadataInfo::GENERATOR_TYPE_CUSTOM, $cmBuilder->getClassMetadata()->generatorType);
        $this->assertEquals(['class' => 'stdClass'], $cmBuilder->getClassMetadata()->customGeneratorDefinition);
    }
}
