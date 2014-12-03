<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Tests\ORM\Tools\Export;

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


    public function testSequenceGenerator() {
        $exporter = new \Doctrine\ORM\Tools\Export\Driver\XmlExporter();
        $metadata = new \Doctrine\ORM\Mapping\ClassMetadata('entityTest');
        $metadata->mapField(array(
            "fieldName" => 'id',
            "type" => 'integer',
            "columnName" => 'id',
            "id" => true,
        ));

        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadataInfo::GENERATOR_TYPE_SEQUENCE);
        $metadata->setSequenceGeneratorDefinition(array(
            'sequenceName' => 'seq_entity_test_id',
            'allocationSize' => 5,
            'initialValue' => 1
        ));


        $fileContent = '<?xml version="1.0" encoding="utf-8"?>'."\n".
'<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping http://doctrine-project.org/schemas/orm/doctrine-mapping.xsd">'."\n".
'  <entity name="entityTest">'."\n".
'    <id name="id" type="integer" column="id">'."\n".
'      <generator strategy="SEQUENCE"/>'."\n".
'      <sequence-generator sequence-name="seq_entity_test_id" allocation-size="5" initial-value="1"/>'."\n".
'    </id>'."\n".
'  </entity>'."\n".
'</doctrine-mapping>'."\n";
        $exportClassMetadata = $exporter->exportClassMetadata($metadata);
        $this->assertEquals($fileContent, $exportClassMetadata);
    }
}
