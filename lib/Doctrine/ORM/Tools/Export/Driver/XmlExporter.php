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
    Doctrine\ORM\Mapping\OneToOneMapping,
    Doctrine\ORM\Mapping\OneToManyMapping,
    Doctrine\ORM\Mapping\ManyToManyMapping;

/**
 * ClassMetadata exporter for Doctrine XML mapping files
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class XmlExporter extends AbstractExporter
{
    protected $_extension = '.dcm.xml';

    /**
     * Converts a single ClassMetadata instance to the exported format
     * and returns it
     *
     * @param ClassMetadataInfo $metadata 
     * @return mixed $exported
     */
    public function exportClassMetadata(ClassMetadataInfo $metadata)
    {
        $xml = new \SimpleXmlElement("<?xml version=\"1.0\" encoding=\"utf-8\"?><doctrine-mapping/>");

        $xml->addAttribute('xmlns', 'http://doctrine-project.org/schemas/orm/doctrine-mapping');
        $xml->addAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $xml->addAttribute('xsi:schemaLocation', 'http://doctrine-project.org/schemas/orm/doctrine-mapping.xsd');

        if ($metadata->isMappedSuperclass) {
            $root = $xml->addChild('mapped-superclass');
        } else {
            $root = $xml->addChild('entity');
        }

        if ($metadata->customRepositoryClassName) {
            $root->addAttribute('repository-class', $metadata->customRepositoryClassName);
        }

        $root->addAttribute('name', $metadata->name);

        if (isset($metadata->primaryTable['name'])) {
            $root->addAttribute('table', $metadata->primaryTable['name']);
        }

        if (isset($metadata->primaryTable['schema'])) {
            $root->addAttribute('schema', $metadata->primaryTable['schema']);
        }

        if (isset($metadata->primaryTable['inheritance-type'])) {
            $root->addAttribute('inheritance-type', $metadata->primaryTable['inheritance-type']);
        }

        if ($metadata->discriminatorColumn) {
            $discriminatorColumnXml = $root->addChild('discriminiator-column');
            $discriminatorColumnXml->addAttribute('name', $metadata->discriminatorColumn['name']);
            $discriminatorColumnXml->addAttribute('type', $metadata->discriminatorColumn['type']);
            $discriminatorColumnXml->addAttribute('length', $metadata->discriminatorColumn['length']);
        }

        if ($metadata->discriminatorMap) {
            $discriminatorMapXml = $root->addChild('discriminator-map');
            foreach ($metadata->discriminatorMap as $value => $className) {
                $discriminatorMappingXml = $discriminatorMapXml->addChild('discriminator-mapping');
                $discriminatorMappingXml->addAttribute('value', $value);
                $discriminatorMappingXml->addAttribute('class', $className);
            }
        }

        $root->addChild('change-tracking-policy', $this->_getChangeTrackingPolicyString($metadata->changeTrackingPolicy));

        if (isset($metadata->primaryTable['indexes'])) {
            $indexesXml = $root->addChild('indexes');
            foreach ($metadata->primaryTable['indexes'] as $name => $index) {
                $indexXml = $root->addChild('index');
                $indexXml->addAttribute('name', $name);
                $indexXml->addAttribute('columns', implode(',', $index['columns']));
            }
        }

        if (isset($metadata->primaryTable['uniqueConstraints'])) {
            $uniqueConstraintsXml = $root->addChild('unique-constraints');
            foreach ($metadata->primaryTable['uniqueConstraints'] as $uniqueConstraint) {
                $uniqueConstraintXml = $uniqueConstraintsXml->addChild('unique-constraint');
                $uniqueConstraintXml->addAttribute('columns', $uniqueConstraint['columns']);
            }
        }

        $fields = $metadata->fieldMappings;
        
        $id = array();
        foreach ($fields as $name => $field) {
            if (isset($field['id']) && $field['id']) {
                $id[$name] = $field;
                unset($fields[$name]);
            }
        }

        if ($idGeneratorType = $this->_getIdGeneratorTypeString($metadata->getIdGeneratorType())) {
            $id[$metadata->getSingleIdentifierFieldName()]['generator']['strategy'] = $idGeneratorType;
        }

        if ($fields) {
            foreach ($fields as $field) {
                $fieldXml = $root->addChild('field');
                $fieldXml->addAttribute('name', $field['fieldName']);
                $fieldXml->addAttribute('type', $field['type']);
                if (isset($field['columnName'])) {
                    $fieldXml->addAttribute('column', $field['columnName']);
                }
                if (isset($field['length'])) {
                    $fieldXml->addAttribute('length', $field['length']);
                }
                if (isset($field['precision'])) {
                    $fieldXml->addAttribute('precision', $field['precision']);
                }
                if (isset($field['scale'])) {
                    $fieldXml->addAttribute('scale', $field['scale']);
                }
                if (isset($field['unique']) && $field['unique']) {
                    $fieldXml->addAttribute('unique', $field['unique']);
                }
                if (isset($field['options'])) {
                    $optionsXml = $fieldXml->addChild('options');
                    foreach ($field['options'] as $key => $value) {
                        $optionsXml->addAttribute($key, $value);
                    }
                }
                if (isset($field['version'])) {
                    $fieldXml->addAttribute('version', $field['version']);
                }
            }
        }

        if ($id) {
            foreach ($id as $field) {
                $idXml = $root->addChild('id');
                $idXml->addAttribute('name', $field['fieldName']);
                $idXml->addAttribute('type', $field['type']);
                if (isset($field['columnName'])) {
                    $idXml->addAttribute('column', $field['columnName']);
                }
                if ($idGeneratorType = $this->_getIdGeneratorTypeString($metadata->generatorType)) {
                    $generatorXml = $idXml->addChild('generator');
                    $generatorXml->addAttribute('strategy', $idGeneratorType);
                }
            }
        }

        foreach ($metadata->associationMappings as $name => $associationMapping) {
            if ($associationMapping instanceof OneToOneMapping) {
                $associationMappingXml = $root->addChild('one-to-one');
            } else if ($associationMapping instanceof OneToManyMapping) {
                $associationMappingXml = $root->addChild('one-to-many');
            } else if ($associationMapping instanceof ManyToManyMapping) {
                $associationMappingXml = $root->addChild('many-to-many');
            }

            $associationMappingXml->addAttribute('field', $associationMapping->sourceFieldName);
            $associationMappingXml->addAttribute('target-entity', $associationMapping->targetEntityName);

            if (isset($associationMapping->mappedByFieldName)) {
                $associationMappingXml->addAttribute('mapped-by', $associationMapping->mappedByFieldName);
            }
            if (isset($associationMapping->orphanRemoval)) {
                $associationMappingXml->addAttribute('orphan-removal', $associationMapping->orphanRemoval);
            }
            if (isset($associationMapping->joinTable) && $associationMapping->joinTable) {
                $joinTableXml = $associationMappingXml->addChild('join-table');
                $joinTableXml->addAttribute('name', $associationMapping->joinTable['name']);
                $joinColumnsXml = $joinTableXml->addChild('join-columns');
                foreach ($associationMapping->joinTable['joinColumns'] as $joinColumn) {
                    $joinColumnXml = $joinColumnsXml->addChild('join-column');
                    $joinColumnXml->addAttribute('name', $joinColumn['name']);
                    $joinColumnXml->addAttribute('referenced-column-name', $joinColumn['referencedColumnName']);
                }
            }

            $cascade = array();
            if ($associationMapping->isCascadeRemove) {
                $cascade[] = 'remove';
            }
            if ($associationMapping->isCascadePersist) {
                $cascade[] = 'persist';
            }
            if ($associationMapping->isCascadeRefresh) {
                $cascade[] = 'refresh';
            }
            if ($associationMapping->isCascadeMerge) {
                $cascade[] = 'merge';
            }
            if ($associationMapping->isCascadeDetach) {
                $cascade[] = 'detach';
            }
            if ($cascade) {
                $cascadeXml = $associationMappingXml->addChild('cascade');
                foreach ($cascade as $type) {
                    $cascadeXml->addChild($type);
                }
            }
        }

        return $this->_asXml($xml);
    }

    /**
     * Code originally taken from
     * http://recurser.com/articles/2007/04/05/format-xml-with-php/
     *
     * @param string $simpleXml 
     * @return string $xml
     */
    private function _asXml($simpleXml)
    {
        $xml = $simpleXml->asXml();

        // add marker linefeeds to aid the pretty-tokeniser (adds a linefeed between all tag-end boundaries)
        $xml = preg_replace('/(>)(<)(\/*)/', "$1\n$2$3", $xml);

        // now indent the tags
        $token = strtok($xml, "\n");
        $result = ''; // holds formatted version as it is built
        $pad = 0; // initial indent
        $matches = array(); // returns from preg_matches()

        // test for the various tag states
        while ($token !== false) {
            // 1. open and closing tags on same line - no change
            if (preg_match('/.+<\/\w[^>]*>$/', $token, $matches)) {
                $indent = 0;
            // 2. closing tag - outdent now
            } else if (preg_match('/^<\/\w/', $token, $matches)) {
                $pad = $pad - 4;
            // 3. opening tag - don't pad this one, only subsequent tags
            } elseif (preg_match('/^<\w[^>]*[^\/]>.*$/', $token, $matches)) {
                $indent = 4;
            // 4. no indentation needed
            } else {
                $indent = 0; 
            }

            // pad the line with the required number of leading spaces
            $line = str_pad($token, strlen($token)+$pad, ' ', STR_PAD_LEFT);
            $result .= $line . "\n"; // add to the cumulative result, with linefeed
            $token = strtok("\n"); // get the next token
            $pad += $indent; // update the pad size for subsequent lines    
        }
        return $result;
    }
}