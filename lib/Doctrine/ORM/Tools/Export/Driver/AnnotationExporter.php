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

use Doctrine\ORM\Mapping\ClassMetadataInfo,
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

    private $_isNew = false;
    private $_outputPath;
    private $_numSpaces = 4;
    private $_classToExtend;
    private $_currentCode;

    /**
     * Converts a single ClassMetadata instance to the exported format
     * and returns it
     *
     * @param ClassMetadataInfo $metadata 
     * @return string $exported
     */
    public function exportClassMetadata(ClassMetadataInfo $metadata)
    {
        if (file_exists($this->_outputPath)) {
            $this->_currentCode = file_get_contents($this->_outputPath);
        }

        ob_start();
        include($this->_isNew ? 'annotation.tpl.php' : 'annotation_body.tpl.php');
        $code = ob_get_contents();
        ob_end_clean();

        $code = str_replace(array('[?php', '?]'), array('<?php', '?>'), $code);
        $code = explode("\n", $code);

        if ($this->_currentCode) {
            $body = $code;
            $code = $this->_currentCode;
            $code = explode("\n", $code);
            unset($code[array_search('}', $code)]);
            foreach ($body as $line) {
                $code[] = $line;
            }
            $code[] = '}';
        }

        $code = array_values($code);

        // Remove empty lines before last "}"
        for ($i = count($code) - 1; $i > 0; --$i) {
            $line = trim($code[$i]);
            if ($line && $line != '}') {
                break;
            }
            if ( ! $line) {
                unset($code[$i]);
            }
        }
        $code = array_values($code);
        $exported = implode("\n", $code);

        return $exported;
    }

    /**
     * Set the number of spaces the exported class should have
     *
     * @param integer $numSpaces 
     * @return void
     */
    public function setNumSpaces($numSpaces)
    {
        $this->_numSpaces = $numSpaces;
    }

    /**
     * Set the name of the class the generated classes should extend from
     *
     * @return void
     */
    public function setClassToExtend($classToExtend)
    {
        $this->_classToExtend = $classToExtend;
    }

    /**
     * This method is overriden so that each class is outputted
     * to the appropriate path where namespaces become directories.
     *
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
            if ( ! file_exists($outputPath)) {
               $this->_isNew = true;
            }
            $this->_outputPath = $outputPath;
            $output = $this->exportClassMetadata($metadata);
            file_put_contents($outputPath, $output);
        }
    }

    private function _hasProperty($property, $metadata)
    {
        if ($this->_isNew) {
            return false;
        } else {
            return strpos($this->_currentCode, '$' . $property) !== false ? true : false;
        }
    }

    private function _hasMethod($method, $metadata)
    {
        if ($this->_isNew) {
            return false;
        } else {
            return strpos($this->_currentCode, 'function ' . $method) !== false ? true : false;
        }
    }

    private function _hasNamespace($metadata)
    {
        return strpos($metadata->name, '\\') ? true : false;
    }

    private function _extendsClass()
    {
        return $this->_classToExtend ? true : false;
    }

    private function _getClassToExtend()
    {
        return $this->_classToExtend;
    }

    private function _getClassToExtendName()
    {
        $refl = new \ReflectionClass($this->getClassToExtend());
        return $refl->getShortName();
    }

    private function _getClassToExtendNamespace()
    {
        $refl = new \ReflectionClass($this->getClassToExtend());
        return $refl->getNamespaceName() ? $refl->getNamespaceName():$refl->getShortName();        
    }

    private function _getClassName($metadata)
    {
        if ($pos = strpos($metadata->name, '\\')) {
            return substr($metadata->name, $pos + 1, strlen($metadata->name));
        } else {
            return $metadata->name;
        }
    }

    private function _getNamespace($metadata)
    {
        return substr($metadata->name, 0, strrpos($metadata->name, '\\'));
    }

    private function _addMethod($type, $fieldName, $metadata, array &$methods)
    {
        $methodName = $type . ucfirst($fieldName);
        if ($this->_hasMethod($methodName, $metadata)) {
            return false;
        }

        $method = array();
        $method[] = str_repeat(' ', $this->_numSpaces) . '/**';
        if ($type == 'get') {
            $method[] = str_repeat(' ', $this->_numSpaces) . ' * Get ' . $fieldName;
        } else if ($type == 'set') {
            $method[] = str_repeat(' ', $this->_numSpaces) . ' * Set ' . $fieldName;
        } else if ($type == 'add') {
            $method[] = str_repeat(' ', $this->_numSpaces) . ' * Add ' . $fieldName;
        }
        $method[] = str_repeat(' ', $this->_numSpaces) . ' */';

        if ($type == 'get') {
            $method[] = str_repeat(' ', $this->_numSpaces) . 'public function ' . $methodName . '()';
        } else if ($type == 'set') {
            $method[] = str_repeat(' ', $this->_numSpaces) . 'public function ' . $methodName . '($value)';
        } else if ($type == 'add') {
            $method[] = str_repeat(' ', $this->_numSpaces) . 'public function ' . $methodName . '($value)';        
        }

        $method[] = str_repeat(' ', $this->_numSpaces) . '{';
        if ($type == 'get') {
            $method[] = str_repeat(' ', $this->_numSpaces) . str_repeat(' ', $this->_numSpaces) . 'return $this->' . $fieldName . ';';
        } else if ($type == 'set') {
            $method[] = str_repeat(' ', $this->_numSpaces) . str_repeat(' ', $this->_numSpaces) . '$this->' . $fieldName . ' = $value;';
        } else if ($type == 'add') {
            $method[] = str_repeat(' ', $this->_numSpaces) . str_repeat(' ', $this->_numSpaces) . '$this->' . $fieldName . '[] = $value;';
        }

        $method[] = str_repeat(' ', $this->_numSpaces) . '}';
        $method[] = "\n";

        $methods[] = implode("\n", $method);
    }

    private function _getMethods($metadata)
    {
      $methods = array();

      foreach ($metadata->fieldMappings as $fieldMapping) {
          $this->_addMethod('set', $fieldMapping['fieldName'], $metadata, $methods);
          $this->_addMethod('get', $fieldMapping['fieldName'], $metadata, $methods);
      }

      foreach ($metadata->associationMappings as $associationMapping) {
          if ($associationMapping instanceof \Doctrine\ORM\Mapping\OneToOneMapping) {
              $this->_addMethod('set', $associationMapping->sourceFieldName, $metadata, $methods);
              $this->_addMethod('get', $associationMapping->sourceFieldName, $metadata, $methods);
          } else if ($associationMapping instanceof \Doctrine\ORM\Mapping\OneToManyMapping) {
              if ($associationMapping->isOwningSide) {
                  $this->_addMethod('set', $associationMapping->sourceFieldName, $metadata, $methods);
                  $this->_addMethod('get', $associationMapping->sourceFieldName, $metadata, $methods);
              } else {
                  $this->_addMethod('add', $associationMapping->sourceFieldName, $metadata, $methods);
                  $this->_addMethod('get', $associationMapping->sourceFieldName, $metadata, $methods);                
              }
          } else if ($associationMapping instanceof \Doctrine\ORM\Mapping\ManyToManyMapping) {
              $this->_addMethod('add', $associationMapping->sourceFieldName, $metadata, $methods);
              $this->_addMethod('get', $associationMapping->sourceFieldName, $metadata, $methods);                
          }
      }

      return $methods;
    }

    private function _getTableAnnotation($metadata)
    {
        $table = array();
        $table[] = 'name="' . $metadata->primaryTable['name'] . '"';
        if (isset($metadata->primaryTable['schema'])) {
            $table[] = 'schema="' . $metadata->primaryTable['schema'] . '"';
        }
        return '@Table(' . implode(', ', $table) . ')';
    }

    private function _getAssociationMappingAnnotation(AssociationMapping $associationMapping, ClassMetadataInfo $metadata)
    {
        // TODO: This function still needs to be written :)
        $lines = array();
        $lines[] = str_repeat(' ', $this->_numSpaces) . '/**';
        $lines[] = str_repeat(' ', $this->_numSpaces) . ' *';
        $lines[] = str_repeat(' ', $this->_numSpaces) . ' */';

        return implode("\n", $lines);
    }

    private function _getFieldMappingAnnotation(array $fieldMapping, ClassMetadataInfo $metadata)
    {
        $lines = array();
        $lines[] = str_repeat(' ', $this->_numSpaces) . '/**';

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
        $lines[] = str_repeat(' ', $this->_numSpaces) . ' * @Column(' . implode(', ', $column) . ')';
        if (isset($fieldMapping['id']) && $fieldMapping['id']) {
            $lines[] = str_repeat(' ', $this->_numSpaces) . ' * @Id';
            if ($generatorType = $this->_getIdGeneratorTypeString($metadata->generatorType)) {
                $lines[] = str_repeat(' ', $this->_numSpaces).' * @GeneratedValue(strategy="' . $generatorType . '")';
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
                $lines[] = str_repeat(' ', $this->_numSpaces) . ' * @SequenceGenerator(' . implode(', ', $sequenceGenerator) . ')';
            }
        }
        if (isset($fieldMapping['version']) && $fieldMapping['version']) {
            $lines[] = str_repeat(' ', $this->_numSpaces) . ' * @Version';
        }
        $lines[] = str_repeat(' ', $this->_numSpaces) . ' */';

        return implode("\n", $lines);
    }
}