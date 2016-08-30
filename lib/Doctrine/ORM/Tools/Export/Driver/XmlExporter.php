<?php
/*
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
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Tools\Export\Driver;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\JoinColumnMetadata;

/**
 * ClassMetadata exporter for Doctrine XML mapping files.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class XmlExporter extends AbstractExporter
{
    /**
     * @var string
     */
    protected $_extension = '.dcm.xml';

    /**
     * {@inheritdoc}
     */
    public function exportClassMetadata(ClassMetadata $metadata)
    {
        $xml = new \SimpleXmlElement("<?xml version=\"1.0\" encoding=\"utf-8\"?><doctrine-mapping ".
            "xmlns=\"http://doctrine-project.org/schemas/orm/doctrine-mapping\" " .
            "xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" ".
            "xsi:schemaLocation=\"http://doctrine-project.org/schemas/orm/doctrine-mapping http://doctrine-project.org/schemas/orm/doctrine-mapping.xsd\" />");

        if ($metadata->isMappedSuperclass) {
            $root = $xml->addChild('mapped-superclass');
        } else {
            $root = $xml->addChild('entity');
        }

        if ($metadata->customRepositoryClassName) {
            $root->addAttribute('repository-class', $metadata->customRepositoryClassName);
        }

        $root->addAttribute('name', $metadata->name);

        if (isset($metadata->table['name'])) {
            $root->addAttribute('table', $metadata->table['name']);
        }

        if (isset($metadata->table['schema'])) {
            $root->addAttribute('schema', $metadata->table['schema']);
        }

        if ($metadata->inheritanceType && $metadata->inheritanceType !== ClassMetadata::INHERITANCE_TYPE_NONE) {
            $root->addAttribute('inheritance-type', $this->_getInheritanceTypeString($metadata->inheritanceType));
        }

        if (isset($metadata->table['options'])) {
            $optionsXml = $root->addChild('options');

            $this->exportTableOptions($optionsXml, $metadata->table['options']);
        }

        if ($metadata->discriminatorColumn) {
            $discrColumn            = $metadata->discriminatorColumn;
            $discriminatorColumnXml = $root->addChild('discriminator-column');

            $discriminatorColumnXml->addAttribute('name', $discrColumn->getColumnName());
            $discriminatorColumnXml->addAttribute('type', $discrColumn->getTypeName());

            if (is_int($discrColumn->getLength())) {
                $discriminatorColumnXml->addAttribute('length', $discrColumn->getLength());
            }

            if (is_int($discrColumn->getScale())) {
                $discriminatorColumnXml->addAttribute('scale', $discrColumn->getScale());
            }

            if (is_int($discrColumn->getPrecision())) {
                $discriminatorColumnXml->addAttribute('precision', $discrColumn->getPrecision());
            }
        }

        if ($metadata->discriminatorMap) {
            $discriminatorMapXml = $root->addChild('discriminator-map');

            foreach ($metadata->discriminatorMap as $value => $className) {
                $discriminatorMappingXml = $discriminatorMapXml->addChild('discriminator-mapping');
                $discriminatorMappingXml->addAttribute('value', $value);
                $discriminatorMappingXml->addAttribute('class', $className);
            }
        }

        $trackingPolicy = $this->_getChangeTrackingPolicyString($metadata->changeTrackingPolicy);

        if ( $trackingPolicy !== 'DEFERRED_IMPLICIT') {
            $root->addChild('change-tracking-policy', $trackingPolicy);
        }

        if (isset($metadata->table['indexes'])) {
            $indexesXml = $root->addChild('indexes');

            foreach ($metadata->table['indexes'] as $name => $index) {
                $indexXml = $indexesXml->addChild('index');
                $indexXml->addAttribute('name', $name);
                $indexXml->addAttribute('columns', implode(',', $index['columns']));

                if (isset($index['flags'])) {
                    $indexXml->addAttribute('flags', implode(',', $index['flags']));
                }
            }
        }

        if (isset($metadata->table['uniqueConstraints'])) {
            $uniqueConstraintsXml = $root->addChild('unique-constraints');

            foreach ($metadata->table['uniqueConstraints'] as $name => $unique) {
                $uniqueConstraintXml = $uniqueConstraintsXml->addChild('unique-constraint');

                $uniqueConstraintXml->addAttribute('name', $name);
                $uniqueConstraintXml->addAttribute('columns', implode(',', $unique['columns']));
            }
        }

        $properties = $metadata->getProperties();
        $id         = [];

        foreach ($properties as $name => $property) {
            if ($property->isPrimaryKey()) {
                $id[$name]['property'] = $property;

                unset($properties[$name]);
            }
        }

        /*foreach ($metadata->associationMappings as $name => $assoc) {
            if (isset($assoc['id']) && $assoc['id']) {
                $id[$name]['associations'] = [
                    'fieldName'      => $name,
                    'associationKey' => true,
                ];
            }
        }*/

        if ( ! $metadata->isIdentifierComposite && $idGeneratorType = $this->_getIdGeneratorTypeString($metadata->generatorType)) {
            $id[$metadata->getSingleIdentifierFieldName()]['generator']['strategy'] = $idGeneratorType;
        }

        if ($id) {
            foreach ($id as $field) {
                $property = $field['property'];
                $idXml    = $root->addChild('id');
                $idXml->addAttribute('name', $property->getName());
                $idXml->addAttribute('type', $property->getTypeName());
                $idXml->addAttribute('column', $property->getColumnName());

                if (is_int($property->getLength())) {
                    $idXml->addAttribute('length', $property->getLength());
                }

                /*if (isset($field['associationKey']) && $field['associationKey']) {
                    $idXml->addAttribute('association-key', 'true');
                }*/

                if ($idGeneratorType = $this->_getIdGeneratorTypeString($metadata->generatorType)) {
                    $generatorXml = $idXml->addChild('generator');

                    $generatorXml->addAttribute('strategy', $idGeneratorType);

                    $this->exportSequenceInformation($idXml, $metadata);
                }
            }
        }

        if ($properties) {
            foreach ($properties as $property) {
                $fieldXml = $root->addChild('field');

                $fieldXml->addAttribute('name', $property->getName());
                $fieldXml->addAttribute('type', $property->getTypeName());
                $fieldXml->addAttribute('column', $property->getColumnName());

                if ($property->isNullable()) {
                    $fieldXml->addAttribute('nullable', 'true');
                }

                if ($property->isUnique()) {
                    $fieldXml->addAttribute('unique', 'true');
                }

                if (is_int($property->getLength())) {
                    $fieldXml->addAttribute('length', $property->getLength());
                }

                if (is_int($property->getPrecision())) {
                    $fieldXml->addAttribute('precision', $property->getPrecision());
                }

                if (is_int($property->getScale())) {
                    $fieldXml->addAttribute('scale', $property->getScale());
                }

                if ($metadata->isVersioned() && $metadata->versionProperty->getName() === $property->getName()) {
                    $fieldXml->addAttribute('version', 'true');
                }

                if ($property->getColumnDefinition()) {
                    $fieldXml->addAttribute('column-definition', $property->getColumnDefinition());
                }

                if ($property->getOptions()) {
                    $optionsXml = $fieldXml->addChild('options');

                    foreach ($property->getOptions() as $key => $value) {
                        $optionXml = $optionsXml->addChild('option', $value);

                        $optionXml->addAttribute('name', $key);
                    }
                }
            }
        }

        $orderMap = [
            ClassMetadata::ONE_TO_ONE,
            ClassMetadata::ONE_TO_MANY,
            ClassMetadata::MANY_TO_ONE,
            ClassMetadata::MANY_TO_MANY,
        ];

        uasort($metadata->associationMappings, function($m1, $m2) use (&$orderMap){
            $a1 = array_search($m1['type'], $orderMap);
            $a2 = array_search($m2['type'], $orderMap);

            return strcmp($a1, $a2);
        });

        foreach ($metadata->associationMappings as $associationMapping) {
            if ($associationMapping['type'] == ClassMetadata::ONE_TO_ONE) {
                $associationMappingXml = $root->addChild('one-to-one');
            } elseif ($associationMapping['type'] == ClassMetadata::MANY_TO_ONE) {
                $associationMappingXml = $root->addChild('many-to-one');
            } elseif ($associationMapping['type'] == ClassMetadata::ONE_TO_MANY) {
                $associationMappingXml = $root->addChild('one-to-many');
            } elseif ($associationMapping['type'] == ClassMetadata::MANY_TO_MANY) {
                $associationMappingXml = $root->addChild('many-to-many');
            }

            $associationMappingXml->addAttribute('field', $associationMapping['fieldName']);
            $associationMappingXml->addAttribute('target-entity', $associationMapping['targetEntity']);

            if (isset($associationMapping['mappedBy'])) {
                $associationMappingXml->addAttribute('mapped-by', $associationMapping['mappedBy']);
            }

            if (isset($associationMapping['inversedBy'])) {
                $associationMappingXml->addAttribute('inversed-by', $associationMapping['inversedBy']);
            }

            if (isset($associationMapping['indexBy'])) {
                $associationMappingXml->addAttribute('index-by', $associationMapping['indexBy']);
            }

            if (isset($associationMapping['orphanRemoval']) && $associationMapping['orphanRemoval'] !== false) {
                $associationMappingXml->addAttribute('orphan-removal', 'true');
            }

            if (isset($associationMapping['fetch'])) {
                $associationMappingXml->addAttribute('fetch', $this->_getFetchModeString($associationMapping['fetch']));
            }

            $cascades = [];

            foreach (['remove', 'persist', 'refresh', 'merge', 'detach'] as $type) {
                if (in_array($type, $associationMapping['cascade'])) {
                    $cascades[] = 'cascade-' . $type;
                }
            }

            if (count($cascades) === 5) {
                $cascades = ['cascade-all'];
            }

            if ($cascades) {
                $cascadeXml = $associationMappingXml->addChild('cascade');

                foreach ($cascades as $type) {
                    $cascadeXml->addChild($type);
                }
            }

            if (isset($associationMapping['joinTable']) && $associationMapping['joinTable']) {
                $joinTableXml = $associationMappingXml->addChild('join-table');

                $joinTableXml->addAttribute('name', $associationMapping['joinTable']['name']);

                $joinColumnsXml = $joinTableXml->addChild('join-columns');

                foreach ($associationMapping['joinTable']['joinColumns'] as $joinColumn) {
                    /** @var JoinColumnMetadata $joinColumn */
                    $joinColumnXml = $joinColumnsXml->addChild('join-column');

                    $joinColumnXml->addAttribute('name', $joinColumn->getColumnName());
                    $joinColumnXml->addAttribute('referenced-column-name', $joinColumn->getReferencedColumnName());

                    if (! empty($joinColumn->getAliasedName())) {
                        $joinColumnXml->addAttribute('field-name', $joinColumn->getAliasedName());
                    }

                    if (! empty($joinColumn->getOnDelete())) {
                        $joinColumnXml->addAttribute('on-delete', $joinColumn->getOnDelete());
                    }

                    if (! empty($joinColumn->getColumnDefinition())) {
                        $joinColumnXml->addAttribute('column-definition', $joinColumn->getColumnDefinition());
                    }

                    if ($joinColumn->isNullable()) {
                        $joinColumnXml->addAttribute('nullable', $joinColumn->isNullable());
                    }

                    if ($joinColumn->isUnique()) {
                        $joinColumnXml->addAttribute('unique', $joinColumn->isUnique());
                    }
                }

                $inverseJoinColumnsXml = $joinTableXml->addChild('inverse-join-columns');

                foreach ($associationMapping['joinTable']['inverseJoinColumns'] as $joinColumn) {
                    /** @var JoinColumnMetadata $joinColumn */
                    $joinColumnXml = $inverseJoinColumnsXml->addChild('join-column');

                    $joinColumnXml->addAttribute('name', $joinColumn->getColumnName());
                    $joinColumnXml->addAttribute('referenced-column-name', $joinColumn->getReferencedColumnName());

                    if (! empty($joinColumn->getAliasedName())) {
                        $joinColumnXml->addAttribute('field-name', $joinColumn->getAliasedName());
                    }

                    if (! empty($joinColumn->getOnDelete())) {
                        $joinColumnXml->addAttribute('on-delete', $joinColumn->getOnDelete());
                    }

                    if (! empty($joinColumn->getColumnDefinition())) {
                        $joinColumnXml->addAttribute('column-definition', $joinColumn->getColumnDefinition());
                    }

                    if ($joinColumn->isNullable()) {
                        $joinColumnXml->addAttribute('nullable', $joinColumn->isNullable());
                    }

                    if ($joinColumn->isUnique()) {
                        $joinColumnXml->addAttribute('unique', $joinColumn->isUnique());
                    }
                }
            }

            if (isset($associationMapping['joinColumns'])) {
                $joinColumnsXml = $associationMappingXml->addChild('join-columns');

                foreach ($associationMapping['joinColumns'] as $joinColumn) {
                    /** @var JoinColumnMetadata $joinColumn */
                    $joinColumnXml = $joinColumnsXml->addChild('join-column');

                    $joinColumnXml->addAttribute('name', $joinColumn->getColumnName());
                    $joinColumnXml->addAttribute('referenced-column-name', $joinColumn->getReferencedColumnName());

                    if (! empty($joinColumn->getAliasedName())) {
                        $joinColumnXml->addAttribute('field-name', $joinColumn->getAliasedName());
                    }

                    if (! empty($joinColumn->getOnDelete())) {
                        $joinColumnXml->addAttribute('on-delete', $joinColumn->getOnDelete());
                    }

                    if (! empty($joinColumn->getColumnDefinition())) {
                        $joinColumnXml->addAttribute('column-definition', $joinColumn->getColumnDefinition());
                    }

                    if ($joinColumn->isNullable()) {
                        $joinColumnXml->addAttribute('nullable', $joinColumn->isNullable());
                    }

                    if ($joinColumn->isUnique()) {
                        $joinColumnXml->addAttribute('unique', $joinColumn->isUnique());
                    }
                }
            }

            if (isset($associationMapping['orderBy'])) {
                $orderByXml = $associationMappingXml->addChild('order-by');

                foreach ($associationMapping['orderBy'] as $name => $direction) {
                    $orderByFieldXml = $orderByXml->addChild('order-by-field');

                    $orderByFieldXml->addAttribute('name', $name);
                    $orderByFieldXml->addAttribute('direction', $direction);
                }
            }
        }

        if (isset($metadata->lifecycleCallbacks) && count($metadata->lifecycleCallbacks)>0) {
            $lifecycleCallbacksXml = $root->addChild('lifecycle-callbacks');

            foreach ($metadata->lifecycleCallbacks as $name => $methods) {
                foreach ($methods as $method) {
                    $lifecycleCallbackXml = $lifecycleCallbacksXml->addChild('lifecycle-callback');

                    $lifecycleCallbackXml->addAttribute('type', $name);
                    $lifecycleCallbackXml->addAttribute('method', $method);
                }
            }
        }

        return $this->_asXml($xml);
    }

    /**
     * Exports (nested) option elements.
     *
     * @param \SimpleXMLElement $parentXml
     * @param array             $options
     */
    private function exportTableOptions(\SimpleXMLElement $parentXml, array $options)
    {
        foreach ($options as $name => $option) {
            $isArray   = is_array($option);
            $optionXml = $isArray
                ? $parentXml->addChild('option')
                : $parentXml->addChild('option', (string) $option);

            $optionXml->addAttribute('name', (string) $name);

            if ($isArray) {
                $this->exportTableOptions($optionXml, $option);
            }
        }
    }

    /**
     * Export sequence information (if available/configured) into the current identifier XML node
     *
     * @param \SimpleXMLElement $identifierXmlNode
     * @param ClassMetadata     $metadata
     *
     * @return void
     */
    private function exportSequenceInformation(\SimpleXMLElement $identifierXmlNode, ClassMetadata $metadata)
    {
        $sequenceDefinition = $metadata->generatorDefinition;

        if (! ($metadata->generatorType === ClassMetadata::GENERATOR_TYPE_SEQUENCE && $sequenceDefinition)) {
            return;
        }

        $sequenceGeneratorXml = $identifierXmlNode->addChild('sequence-generator');

        $sequenceGeneratorXml->addAttribute('sequence-name', $sequenceDefinition['sequenceName']);
        $sequenceGeneratorXml->addAttribute('allocation-size', $sequenceDefinition['allocationSize']);
    }

    /**
     * @param \SimpleXMLElement $simpleXml
     *
     * @return string $xml
     */
    private function _asXml($simpleXml)
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');

        $dom->loadXML($simpleXml->asXML());
        $dom->formatOutput = true;

        return $dom->saveXML();
    }
}
