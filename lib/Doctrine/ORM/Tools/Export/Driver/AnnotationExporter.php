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
    private $_numSpaces;
    private $_spaces;
    private $_classToExtend;
    private $_currentCode;

    public function __construct($dir = null)
    {
        parent::__construct($dir);
        $this->setNumSpaces(4);
    }

    /**
     * Converts a single ClassMetadata instance to the exported format
     * and returns it
     *
     * @param ClassMetadataInfo $metadata 
     * @return string $exported
     */
    public function exportClassMetadata(ClassMetadataInfo $metadata)
    {
        $this->_currentCode = null;
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
        $this->_spaces = str_repeat(' ', $numSpaces);
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
        $method[] = $this->_spaces . '/**';
        if ($type == 'get') {
            $method[] = $this->_spaces . ' * Get ' . $fieldName;
        } else if ($type == 'set') {
            $method[] = $this->_spaces . ' * Set ' . $fieldName;
        } else if ($type == 'add') {
            $method[] = $this->_spaces . ' * Add ' . $fieldName;
        }
        $method[] = $this->_spaces . ' */';

        if ($type == 'get') {
            $method[] = $this->_spaces . 'public function ' . $methodName . '()';
        } else if ($type == 'set') {
            $method[] = $this->_spaces . 'public function ' . $methodName . '($value)';
        } else if ($type == 'add') {
            $method[] = $this->_spaces . 'public function ' . $methodName . '($value)';        
        }

        $method[] = $this->_spaces . '{';
        if ($type == 'get') {
            $method[] = $this->_spaces . $this->_spaces . 'return $this->' . $fieldName . ';';
        } else if ($type == 'set') {
            $method[] = $this->_spaces . $this->_spaces . '$this->' . $fieldName . ' = $value;';
        } else if ($type == 'add') {
            $method[] = $this->_spaces . $this->_spaces . '$this->' . $fieldName . '[] = $value;';
        }

        $method[] = $this->_spaces . '}';
        $method[] = "\n";

        $methods[] = implode("\n", $method);
    }

    private function _getMethods($metadata)
    {
      $methods = array();

      foreach ($metadata->fieldMappings as $fieldMapping) {
          if ( ! isset($fieldMapping['id']) || ! $fieldMapping['id']) {
              $this->_addMethod('set', $fieldMapping['fieldName'], $metadata, $methods);
          }

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

    private function _getJoinColumnAnnotation(array $joinColumn)
    {
        $joinColumnAnnot = array();
        if (isset($joinColumn['name'])) {
            $joinColumnAnnot[] = 'name="' . $joinColumn['name'] . '"';
        }
        if (isset($joinColumn['referencedColumnName'])) {
            $joinColumnAnnot[] = 'referencedColumnName="' . $joinColumn['referencedColumnName'] . '"';
        }
        if (isset($joinColumn['unique']) && $joinColumn['unique']) {
            $joinColumnAnnot[] = 'unique=' . ($joinColumn['unique'] ? 'true' : 'false');
        }
        if (isset($joinColumn['nullable'])) {
            $joinColumnAnnot[] = 'nullable=' . ($joinColumn['nullable'] ? 'true' : 'false');
        }
        if (isset($joinColumn['onDelete'])) {
            $joinColumnAnnot[] = 'onDelete=' . ($joinColumn['onDelete'] ? 'true' : 'false');
        }
        if (isset($joinColumn['onUpdate'])) {
            $joinColumnAnnot[] = 'onUpdate=' . ($joinColumn['onUpdate'] ? 'true' : 'false');
        }
        return '@JoinColumn(' . implode(', ', $joinColumnAnnot) . ')';
    }

    private function _getAssociationMappingAnnotation(AssociationMapping $associationMapping, ClassMetadataInfo $metadata)
    {
        $e = explode('\\', get_class($associationMapping));
        $type = str_replace('Mapping', '', end($e));
        $typeOptions = array();
        if (isset($associationMapping->targetEntityName)) {
            $typeOptions[] = 'targetEntity="' . $associationMapping->targetEntityName . '"';
        }
        if (isset($associationMapping->mappedByFieldName)) {
            $typeOptions[] = 'mappedBy="' . $associationMapping->mappedByFieldName . '"';
        }
        if ($associationMapping->hasCascades()) {
            $cascades = array();
            if ($this->isCascadePersist) $cascades[] = '"persist"';
            if ($this->isCascadeRemove) $cascades[] = '"remove"';
            if ($this->isCascadeDetach) $cascades[] = '"detach"';
            if ($this->isCascadeMerge) $cascades[] = '"merge"';
            if ($this->isCascadeRefresh) $cascades[] = '"refresh"';
            $typeOptions[] = 'cascade={' . implode(',', $cascades) . '}';            
        }
        if (isset($associationMapping->orphanRemoval) && $associationMapping->orphanRemoval) {
            $typeOptions[] = 'orphanRemoval=' . ($associationMapping->orphanRemoval ? 'true' : 'false');
        }

        $lines = array();
        $lines[] = $this->_spaces . '/**';
        $lines[] = $this->_spaces . ' * @' . $type . '(' . implode(', ', $typeOptions) . ')';

        if (isset($associationMapping->joinColumns) && $associationMapping->joinColumns) {
            $lines[] = $this->_spaces . ' * @JoinColumns({';

            $joinColumnsLines = array();
            foreach ($associationMapping->joinColumns as $joinColumn) {
                if ($joinColumnAnnot = $this->_getJoinColumnAnnotation($joinColumn)) {
                    $joinColumnsLines[] = $this->_spaces . ' *   ' . $joinColumnAnnot;
                }
            }
            $lines[] = implode(",\n", $joinColumnsLines);
            $lines[] = $this->_spaces . ' * })';
        }

        if (isset($associationMapping->joinTable) && $associationMapping->joinTable) {
            $joinTable = array();
            $joinTable[] = 'name="' . $associationMapping->joinTable['name'] . '"';
            if (isset($associationMapping->joinTable['schema'])) {
                $joinTable[] = 'schema="' . $associationMapping->joinTable['schema'] . '"';
            }

            $lines[] = $this->_spaces . ' * @JoinTable(' . implode(', ', $joinTable) . ',';

            $lines[] = $this->_spaces . ' *   joinColumns={';
            foreach ($associationMapping->joinTable['joinColumns'] as $joinColumn) {
                $lines[] = $this->_spaces . ' *     ' . $this->_getJoinColumnAnnotation($joinColumn);
            }
            $lines[] = $this->_spaces . ' *   },';

            $lines[] = $this->_spaces . ' *   inverseJoinColumns={';
            foreach ($associationMapping->joinTable['inverseJoinColumns'] as $joinColumn) {
                $lines[] = $this->_spaces . ' *     ' . $this->_getJoinColumnAnnotation($joinColumn);
            }
            $lines[] = $this->_spaces . ' *   }';

            $lines[] = $this->_spaces . ' * )';
        }

        $lines[] = $this->_spaces . ' */';

        return implode("\n", $lines);
    }

    private function _getFieldMappingAnnotation(array $fieldMapping, ClassMetadataInfo $metadata)
    {
        $lines = array();
        $lines[] = $this->_spaces . '/**';

        $column = array();
        if (isset($fieldMapping['columnName'])) {
            $column[] = 'name="' . $fieldMapping['columnName'] . '"';
        }
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
            if ($options) {
                $column[] = 'options={' . implode(', ', $options) . '}';
            }
        }
        if (isset($fieldMapping['unique'])) {
            $column[] = 'unique=' . var_export($fieldMapping['unique'], true);
        }
        $lines[] = $this->_spaces . ' * @Column(' . implode(', ', $column) . ')';
        if (isset($fieldMapping['id']) && $fieldMapping['id']) {
            $lines[] = $this->_spaces . ' * @Id';
            if ($generatorType = $this->_getIdGeneratorTypeString($metadata->generatorType)) {
                $lines[] = $this->_spaces.' * @GeneratedValue(strategy="' . $generatorType . '")';
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
                $lines[] = $this->_spaces . ' * @SequenceGenerator(' . implode(', ', $sequenceGenerator) . ')';
            }
        }
        if (isset($fieldMapping['version']) && $fieldMapping['version']) {
            $lines[] = $this->_spaces . ' * @Version';
        }
        $lines[] = $this->_spaces . ' */';

        return implode("\n", $lines);
    }
}