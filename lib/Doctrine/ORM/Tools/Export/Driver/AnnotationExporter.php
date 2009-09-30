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

namespace Doctrine\ORM\Tools\Export\Driver;

use Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\Mapping\AssociationMapping;

/**
 * ClassMetadata exporter for PHP classes with annotations
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class AnnotationExporter extends AbstractExporter
{
    protected $_extension = '.php';

    /**
     * Converts a single ClassMetadata instance to the exported format
     * and returns it
     *
     * @param ClassMetadata $metadata 
     * @return mixed $exported
     */
    public function exportClassMetadata(ClassMetadata $metadata)
    {
        ob_start();
        include('annotation.tpl.php');
        $code = ob_get_contents();
        ob_end_clean();

        $code = str_replace(array('[?php', '?]'), array('<?php', '?>'), $code);
        return $code;
    }

    private function _getTableAnnotation($metadata)
    {
        $table = array();
        $table[] = 'name=' . $metadata->primaryTable['name'];
        if (isset($metadata->primaryTable['schema'])) {
            $table[] = 'schema=' . $metadata->primaryTable['schema'];
        }
        return '@Table(' . implode(', ', $table) . ')';
    }

    private function _getAssociationMappingAnnotation(AssociationMapping $associationMapping, ClassMetadata $metadata)
    {
        // TODO: This function still needs to be written :)
        $lines = array();
        $lines[] = '    /**';
        $lines[] = '     *';
        $lines[] = '     */';

        return implode("\n", $lines);
    }

    private function _getFieldMappingAnnotation(array $fieldMapping, ClassMetadata $metadata)
    {
        $lines = array();
        $lines[] = '    /**';

        $column = array();
        if (isset($fieldMapping['type'])) {
            $column[] = 'type="' . $fieldMapping['type'] . '"';
        }
        if (isset($fieldMapping['length'])) {
            $column[] = 'length=' . $fieldMapping['length'];
        }
        if (isset($fieldMapping['precision'])) {
            $column[] = 'precision=' .  $fieldMapping['precision'];
        }
        if (isset($fieldMapping['scale'])) {
            $column[] = 'scale=' . $fieldMapping['scale'];
        }
        if (isset($fieldMapping['nullable'])) {
            $column[] = 'nullable=' .  var_export($fieldMapping['nullable'], true);
        }
        if (isset($fieldMapping['options'])) {
            $options = array();
            foreach ($fieldMapping['options'] as $key => $value) {
                $value = var_export($value, true);
                $value = str_replace("'", '"', $value);
                $options[] = ! is_numeric($key) ? $key . '=' . $value:$value;
            }
            $column[] = 'options={' . implode(', ', $options) . '}';
        }
        if (isset($fieldMapping['unique'])) {
            $column[] = 'unique=' . var_export($fieldMapping['unique'], true);
        }
        $lines[] = '     * @Column(' . implode(', ', $column) . ')';
        if (isset($fieldMapping['id']) && $fieldMapping['id']) {
            $lines[] = '     * @Id';
            if ($generatorType = $this->_getIdGeneratorTypeString($metadata->generatorType)) {
                $lines[] = '     * @GeneratedValue(strategy="' . $generatorType . '")';
            }
            if ($metadata->sequenceGeneratorDefinition) {
                $sequenceGenerator = array();
                if (isset($metadata->sequenceGeneratorDefinition['sequenceName'])) {
                    $sequenceGenerator[] = 'sequenceName="' . $metadata->sequenceGeneratorDefinition['sequenceName'] . '"';
                }
                if (isset($metadata->sequenceGeneratorDefinition['allocationSize'])) {
                    $sequenceGenerator[] = 'allocationSize="' . $metadata->sequenceGeneratorDefinition['allocationSize'] . '"';
                }
                if (isset($metadata->sequenceGeneratorDefinition['initialValue'])) {
                    $sequenceGenerator[] = 'initialValue="' . $metadata->sequenceGeneratorDefinition['initialValue'] . '"';
                }
                $lines[] = '     * @SequenceGenerator(' . implode(', ', $sequenceGenerator) . ')';
            }
        }
        if (isset($fieldMapping['version']) && $fieldMapping['version']) {
            $lines[] = '     * @Version';
        }
        $lines[] = '     */';

        return implode("\n", $lines);
    }

    /**
     * Export each ClassMetadata instance to a single Doctrine Mapping file
     * named after the entity
     *
     * @return void
     */
    public function export()
    {
        if ( ! is_dir($this->_outputDir)) {
            mkdir($this->_outputDir, 0777);
        }

        foreach ($this->_metadatas as $metadata) {
            $outputPath = $this->_outputDir . '/' . str_replace('\\', DIRECTORY_SEPARATOR, $metadata->name) . $this->_extension;
            $outputDir = dirname($outputPath);
            if ( ! is_dir($outputDir)) {
                mkdir($outputDir, 0777, true);
            }
            $output = $this->exportClassMetadata($metadata);
            file_put_contents($outputPath, $output);
        }
    }
}