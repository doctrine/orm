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

namespace Doctrine\ORM\Mapping\Driver\Configuration;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * @author Kim Hemsø Rasmussen <kimhemsoe@gmail.com>
 */
class YamlMappingConfiguration implements ConfigurationInterface
{
    private $configurationExtensions = array();

    public function __construct(array $configurationExtensions)
    {
        $this->configurationExtensions = $configurationExtensions;
    }

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $root = $treeBuilder->root('entity')
            ->useAttributeAsKey('_FIXME_');
        $prototype = $root->prototype('array');

        $prototype
                ->children()
                    ->scalarNode('type')
                        ->isRequired()
                    ->end()
                    ->scalarNode('table')->end()
                    ->booleanNode('readOnly')->end()
                    ->scalarNode('inheritanceType')->end()
                    ->scalarNode('repositoryClass')->end()
                    ->arrayNode('discriminatorMap')
                        ->prototype('scalar')->end()
                    ->end()
                    ->append($this->getDiscriminatorColumnNode())
                    ->scalarNode('changeTrackingPolicy')->end()
                    ->variableNode('associationOverride')->end() // Fixme
                    ->append($this->getFieldsNode('attributeOverride'))
                    ->append($this->getIdNode())
                    ->append($this->getOptionsNode())
                    ->append($this->getFieldsNode('fields'))
                    ->append($this->getToOneNode('oneToOne'))
                    ->append($this->getToOneNode('manyToOne'))
                    ->append($this->getManyToManyNode())
                    ->append($this->getOneToManyNode())
                    ->append($this->getNamedQueriesNode())
                    ->append($this->getLifecycleCallbacksNode())
                    ->append($this->getNamedNativeQueriesNode())
                    ->append($this->getConstraintsNode('uniqueConstraints'))
                    ->append($this->getConstraintsNode('indexes'))
                    ->append($this->getSqlResultSetMappingsNode())
                    ->append($this->getEntityListeners())
                ->end()
            ->end();

        foreach ($this->configurationExtensions as $extension) {
            $extension->addConfiguration($prototype);
        }

        return $treeBuilder;
    }

    private function getIdNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('id');
        $node
            ->prototype('array')
                ->children()
                    ->booleanNode('associationKey')->end()
                    ->scalarNode('type')->end()
                    ->scalarNode('column')->end()
                    ->scalarNode('length')->end()
                    ->booleanNode('unsigned')->end()
                    ->scalarNode('columnDefinition')->end()
                    ->arrayNode('generator')
                        ->children()
                            ->scalarNode('strategy')->end()
                        ->end()
                    ->end()
                    ->arrayNode('sequenceGenerator')
                        ->children()
                            ->scalarNode('sequenceName')->end()
                            ->scalarNode('allocationSize')->end()
                            ->scalarNode('initialValue')->end()
                        ->end()
                    ->end()
                    ->arrayNode('customIdGenerator')
                        ->children()
                            ->scalarNode('class')->end()
                        ->end()
                    ->end()
                    ->variableNode('tableGenerator')->end()
                ->end()
            ->end()
        ->end();

        return $node;
    }

    private function getFieldsNode($name)
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root($name);
        $prototype = $node->prototype('array');
        $prototype
            ->children()
                ->booleanNode('id')->end()
                ->arrayNode('generator')
                    ->children()
                        ->scalarNode('strategy')->end()
                    ->end()
                ->end()
                ->scalarNode('type')->end()
                ->scalarNode('column')->end()
                ->scalarNode('length')->end()
                ->scalarNode('precision')->end()
                ->scalarNode('scale')->end()
                ->booleanNode('nullable')->end()
                ->booleanNode('notnull')->end()
                ->booleanNode('unique')->end()
                ->scalarNode('columnDefinition')->end()
                ->scalarNode('version')->end()
                ->append($this->getOptionsNode())
            ->end();

        if ('fields' == $name) {
            foreach ($this->configurationExtensions as $extension) {
                $extension->addFieldConfiguration($prototype);
            }
        }

        return $node;
    }

    private function getManyToManyNode()
    {
        // TODO add validation for that mappedBy can't be set at the same time.

        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('manyToMany');
        $prototype = $node->prototype('array');
        $prototype
            ->children()
                ->scalarNode('targetEntity')->end()
                ->scalarNode('fetch')->end()
                ->scalarNode('mappedBy')->end()
                ->scalarNode('inversedBy')->end()
                ->scalarNode('indexBy')->end()
                ->arrayNode('joinTable')
                    ->children()
                        ->scalarNode('name')->end()
                        ->scalarNode('schema')->end()
                        ->booleanNode('orphanRemoval')->end()
                        ->append($this->getJoinColumnNode('joinColumns', true))
                        ->append($this->getJoinColumnNode('inverseJoinColumns', true))
                    ->end()
                ->end()
                ->append($this->getCascadeNode())
                ->append($this->getOrderByNode())
            ->end();

        foreach ($this->configurationExtensions as $extension) {
            $extension->addManyToManyConfiguration($prototype);
        }

        return $node;
    }

    private function getOneToManyNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('oneToMany');
        $prototype = $node->prototype('array');
        $prototype
            ->children()
                ->scalarNode('targetEntity')->end()
                ->booleanNode('orphanRemoval')->end()
                ->scalarNode('fetch')->end()
                ->scalarNode('mappedBy')->end()
                ->scalarNode('inversedBy')->end()
                ->scalarNode('indexBy')->end()
                ->append($this->getCascadeNode())
                ->append($this->getOrderByNode())
            ->end();

        foreach ($this->configurationExtensions as $extension) {
            $extension->addOneToManyConfiguration($prototype);
        }

        return $node;
    }


    private function getDiscriminatorColumnNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('discriminatorColumn');
        $node
            ->children()
                ->scalarNode('name')->end()
                ->scalarNode('type')->end()
                ->scalarNode('length')->end()
                ->scalarNode('columnDefinition')->end()
            ->end();

        return $node;
    }

    private function getNamedNativeQueriesNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('namedNativeQueries');
        $node
            ->normalizeKeys(false)
            ->protoType('array')
                ->children()
                    ->scalarNode('name')->end()
                    ->scalarNode('resultSetMapping')->end()
                    ->scalarNode('query')->end()
                    ->scalarNode('resultClass')->end()
                ->end()
            ->end();

        return $node;
    }

    private function getCascadeNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('cascade');
        $node
            ->prototype('scalar')->end();

        return $node;
    }

    private function getOrderByNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('orderBy');
        $node
            ->prototype('scalar')->end();

        return $node;
    }

    private function getlifecycleCallbacksEventNode($name)
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root($name);
        $node
            ->prototype('scalar')->end()
            ;

        return $node;
    }

    private function getConstraintsNode($name)
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root($name);
        $node
            ->prototype('array')
                ->children()
                    ->scalarNode('name')->end()
                    ->arrayNode('columns')
                        ->protoType('scalar')->end()
                        ->beforeNormalization()
                        ->ifString()
                            ->then(function ($value) {
                                return array_map('trim', explode(',', $value));
                            })
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }

    private function getToOneNode($name)
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root($name);
        $prototype = $node->prototype('array');
        $prototype
            ->children()
                ->scalarNode('targetEntity')->end()
                ->scalarNode('mappedBy')->end()
                ->scalarNode('inversedBy')->end()
                ->scalarNode('fetch')->end()
                ->booleanNode('orphanRemoval')->end()
                ->append($this->getJoinColumnNode('joinColumn', false))
                ->append($this->getJoinColumnNode('joinColumns', true))
                ->append($this->getCascadeNode())
            ->end();

        if ($name == 'oneToOne') {
            foreach ($this->configurationExtensions as $extension) {
                $extension->addOneToOneConfiguration($prototype);
            }
        } else {
            foreach ($this->configurationExtensions as $extension) {
                $extension->addManyToOneConfiguration($prototype);
            }
        }

        return $node;
    }

    private function getOptionsNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('options');
        $node
            ->protoType('variable');

        return $node;
    }

    private function getJoinColumnNode($name, $prototype)
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root($name);
        $array = $node;
        if ($prototype) {
            $array = $node->prototype('array');
        }

        $array
            ->children()
                ->scalarNode('name')->end()
                ->scalarNode('fieldName')->end()
                ->booleanNode('nullable')->end()
                ->booleanNode('unique')->end()
                ->scalarNode('referencedColumnName')->end()
                ->scalarNode('onDelete')->end()
                ->scalarNode('columnDefinition')->end()
            ->end();

        return $node;
    }

    private function getNamedQueriesNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('namedQueries');
        $node
            ->normalizeKeys(false)
            ->prototype('array')
                ->beforeNormalization()
                    ->ifString()
                        ->then(function ($value) {
                            return array('query' => $value);
                        })
                ->end()
                ->children()
                    ->scalarNode('name')->end()
                    ->scalarNode('query')->end()
                ->end()
            ->end()
                            ;
        return $node;
    }

    private function getLifecycleCallbacksNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('lifecycleCallbacks');
        $node
            ->children()
                ->append($this->getlifecycleCallbacksEventNode('prePersist'))
                ->append($this->getlifecycleCallbacksEventNode('postPersist'))
            ->end();

        return $node;
    }

    private function getSqlResultSetMappingsNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('sqlResultSetMappings');
        $node
            ->normalizeKeys(false)
            ->protoType('array')
                ->children()
                    ->scalarNode('name')->end()
                    ->arrayNode('columnResult')
                        ->prototype('array')
                            ->children()
                                ->scalarNode('name')->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('entityResult')
                        ->prototype('array')
                            ->children()
                                ->scalarNode('entityClass')->end()
                                ->scalarNode('discriminatorColumn')->end()
                                ->arrayNode('fieldResult')
                                    ->prototype('array')
                                        ->children()
                                            ->scalarNode('name')->end()
                                            ->scalarNode('column')->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }

    private function getEntityListeners()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('entityListeners');
        $node
            ->prototype('array')
                ->children()

                    ->arrayNode('preFlush')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('postLoad')

                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('postPersist')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('prePersist')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('postUpdate')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('preUpdate')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('postRemove')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('preRemove')
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }
}
