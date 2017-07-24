<?php

namespace Doctrine\Tests\ORM\Tools\Export;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\Export\Driver\XmlExporter;

/**
 * Test case for XmlClassMetadataExporterTest
 *
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class XmlClassMetadataExporterTest extends AbstractClassMetadataExporterTest
{
    protected function _getType()
    {
        return 'xml';
    }

    /**
     * @group DDC-3428
     */
    public function testSequenceGenerator() {
        $exporter = new XmlExporter();
        $metadata = new ClassMetadata('entityTest');

        $metadata->mapField(
            [
            "fieldName" => 'id',
            "type" => 'integer',
            "columnName" => 'id',
            "id" => true,
            ]
        );

        $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_SEQUENCE);
        $metadata->setSequenceGeneratorDefinition(
            [
            'sequenceName' => 'seq_entity_test_id',
            'allocationSize' => 5,
            'initialValue' => 1
            ]
        );

        $expectedFileContent = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<doctrine-mapping
    xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping http://doctrine-project.org/schemas/orm/doctrine-mapping.xsd"
>
  <entity name="entityTest">
    <id name="id" type="integer" column="id">
      <generator strategy="SEQUENCE"/>
      <sequence-generator sequence-name="seq_entity_test_id" allocation-size="5" initial-value="1"/>
    </id>
  </entity>
</doctrine-mapping>
XML;

        $this->assertXmlStringEqualsXmlString($expectedFileContent, $exporter->exportClassMetadata($metadata));
    }

    /**
     * @group 1214
     * @group 1216
     * @group DDC-3439
     */
    public function testFieldOptionsExport() {
        $exporter = new XmlExporter();
        $metadata = new ClassMetadata('entityTest');

        $metadata->mapField(
            [
            "fieldName" => 'myField',
            "type" => 'string',
            "columnName" => 'my_field',
            "options" => [
                "default" => "default_string",
                "comment" => "The comment for the field",
            ],
            ]
        );

        $expectedFileContent = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping http://doctrine-project.org/schemas/orm/doctrine-mapping.xsd">
  <entity name="entityTest">
    <field name="myField" type="string" column="my_field">
      <options>
        <option name="default">default_string</option>
        <option name="comment">The comment for the field</option>
      </options>
    </field>
  </entity>
</doctrine-mapping>
XML;

        $this->assertXmlStringEqualsXmlString($expectedFileContent, $exporter->exportClassMetadata($metadata));
    }

    /**
     * @group DDC-2632
     */
    public function testFKDefaultValueOptionExportXmlNoNullable() {
        $exporter = new XmlExporter();

        $metadata = $this->getMetadatasDCC2632Nonullable();

        $expetedResult = "<join-columnname=\"user_id\"referenced-column-name=\"id\"nullable=\"\"/></join-columns>";
        $this->assertContains($expetedResult, $string = trim(preg_replace('/\s+/', '', preg_replace('/\t/', '', $exporter->exportClassMetadata($metadata['Ddc2059Project'])))));
    }
    /**
     * @group DDC-2632
     */
    public function testFKDefaultValueOptionExportXmlNullable() {
        $exporter = new XmlExporter();

        $metadata = $this->getMetadatasDCC2632Nullable();

        $expetedResult = "<join-columnname=\"user_id\"referenced-column-name=\"id\"/></join-columns>";
        $this->assertContains($expetedResult, $string = trim(preg_replace('/\s+/', '', preg_replace('/\t/', '', $exporter->exportClassMetadata($metadata['Ddc2059Project'])))));
    }
}
