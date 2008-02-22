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
 * <http://www.phpdoctrine.org>.
 */

/**
 * Doctrine_Builder_Record
 *
 * Import builder is responsible of building Doctrine_Record classes
 * based on a database schema.
 *
 * @package     Doctrine
 * @subpackage  Builder
 * @link        www.phpdoctrine.org
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Jukka Hassinen <Jukka.Hassinen@BrainAlliance.com>
 * @author      Nicolas Bérard-Nault <nicobn@php.net>
 * @author      Jonathan H. Wage <jwage@mac.com>
 */
class Doctrine_Builder_Record
{
    /**
     * Path
     *
     * the path where imported files are being generated
     *
     * @var string $_path
     */
    protected $_path = '';

    /**
     * packagesPrefix
     *
     * @var string
     */
    protected $_packagesPrefix = 'Package';

    /**
     * packagesPath
     *
     * @var string
     */
    protected $_packagesPath = '';

    /**
     * suffix
     *
     * File suffix to use when writing class definitions
     *
     * @var string $suffix
     */
    protected $_suffix = '.php';

    /**
     * generateBaseClasses
     *
     * Bool true/false for whether or not to generate base classes
     *
     * @var string $suffix
     */
    protected $_generateBaseClasses = true;

    /**
     * baseClassesDirectory
     *
     * Directory to put the generate base classes in
     *
     * @var string $suffix
     */
    protected $_baseClassesDirectory = 'generated';

    /**
     * baseClassName
     *
     * @var string
     */
    protected $_baseClassName = 'Doctrine_Record';

    /**
     * tpl
     *
     * Class template used for writing classes
     *
     * @var $_tpl
     */
    protected static $_tpl;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        $this->loadTemplate();
    }

    /**
     * setTargetPath
     *
     * @param string path   the path where imported files are being generated
     * @return
     */
    public function setTargetPath($path)
    {
        if ( ! $this->_packagesPath) {
            $this->setPackagesPath($path . DIRECTORY_SEPARATOR . 'packages');
        }

        $this->_path = $path;
    }

    /**
     * setPackagePath
     *
     * @param string $packagesPrefix
     * @return void
     */
    public function setPackagesPrefix($packagesPrefix)
    {
        $this->_packagesPrefix = $packagesPrefix;
    }

    /**
     * setPackagesPath
     *
     * @param string $packagesPath
     * @return void
     */
    public function setPackagesPath($packagesPath)
    {
        $this->_packagesPath = $packagesPath;
    }

    /**
     * generateBaseClasses
     *
     * Specify whether or not to generate classes which extend from generated base classes
     *
     * @param string $bool
     * @return void
     */
    public function generateBaseClasses($bool = null)
    {
        if ($bool !== null) {
            $this->_generateBaseClasses = $bool;
        }

        return $this->_generateBaseClasses;
    }

    /**
     * setBaseClassesDirectory
     *
     * @return void
     */
    public function setBaseClassesDirectory($baseClassesDirectory)
    {
        $this->_baseClassesDirectory;
    }

    /**
     * setBaseClassName
     *
     * @package default
     */
    public function setBaseClassName($className)
    {
        $this->_baseClassName = $className;
    }

    /**
     * setSuffix
     *
     * @param string $suffix
     * @return void
     */
    public function setSuffix($suffix)
    {
        $this->_suffix = $suffix;
    }

    /**
     * getTargetPath
     *
     * @return string       the path where imported files are being generated
     */
    public function getTargetPath()
    {
        return $this->_path;
    }

    /**
     * setOptions
     *
     * @param string $options
     * @return void
     */
    public function setOptions($options)
    {
        if (!empty($options)) {
            foreach ($options as $key => $value) {
                $this->setOption($key, $value);
            }
        }
    }

    /**
     * setOption
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public function setOption($key, $value)
    {
        $name = 'set' . Doctrine::classify($key);

        if (method_exists($this, $name)) {
            $this->$name($value);
        } else {
            $key = '_' . $key;
            $this->$key = $value;
        }
    }

    /**
     * loadTemplate
     *
     * Loads the class template used for generating classes
     *
     * @return void
     */
    public function loadTemplate()
    {
        if (isset(self::$_tpl)) {
            return;
        }

        self::$_tpl =<<<END
/**
 * This class has been auto-generated by the Doctrine ORM Framework
 */
%sclass %s extends %s
{
%s
%s
%s
}
END;
    }

    /*
     * Build the table definition of a Doctrine_Record object
     *
     * @param  string $table
     * @param  array  $tableColumns
     */
    public function buildTableDefinition(array $definition)
    {
        // If the inheritance type if simple or column aggregation then we do not need a table definition
        if (isset($definition['inheritance']['type']) && ($definition['inheritance']['type'] == 'simple' || $definition['inheritance']['type'] == 'aggregation')) {
            return;
        }

        $ret = array();

        $i = 0;

        if (isset($definition['inheritance']['extends']) && ! (isset($definition['override_parent']) && $definition['override_parent'] == true)) {
            $ret[$i] = "    parent::setTableDefinition();";
            $i++;
        }

        if (isset($definition['tableName']) && !empty($definition['tableName'])) {
            $ret[$i] = "    ".'$this->setTableName(\''. $definition['tableName'].'\');';

            $i++;
        }

        if (isset($definition['columns']) && is_array($definition['columns']) && !empty($definition['columns'])) {
            $ret[$i] = $this->buildColumns($definition['columns']);
            $i++;
        }

        if (isset($definition['indexes']) && is_array($definition['indexes']) && !empty($definition['indexes'])) {
            $ret[$i] = $this->buildIndexes($definition['indexes']);
            $i++;
        }

        if (isset($definition['attributes']) && is_array($definition['attributes']) && !empty($definition['attributes'])) {
            $ret[$i] = $this->buildAttributes($definition['attributes']);
            $i++;
        }

        if (isset($definition['options']) && is_array($definition['options']) && !empty($definition['options'])) {
            $ret[$i] = $this->buildOptions($definition['options']);
            $i++;
        }

        if (isset($definition['subclasses']) && is_array($definition['subclasses']) && !empty($definition['subclasses'])) {
            $ret[$i] = '    $this->setSubclasses(' . var_export($definition['subclasses'], true) . ');';
            $i++;
        }

        $code = implode("\n", $ret);
        $code = trim($code);

        if ($code) {
          return "\n  public function setTableDefinition()"."\n  {\n    ".$code."\n  }";
        }
    }

    /**
     * buildSetUp
     *
     * @param  array $options
     * @param  array $columns
     * @param  array $relations
     * @return string
     */
    public function buildSetUp(array $definition)
    {
        $ret = array();
        $i = 0;

        if (isset($definition['inheritance']['extends']) && ! (isset($definition['override_parent']) && $definition['override_parent'] == true)) {
            $ret[$i] = "    parent::setUp();";
            $i++;
        }

        if (isset($definition['relations']) && is_array($definition['relations']) && !empty($definition['relations'])) {
            foreach ($definition['relations'] as $name => $relation) {
                $class = isset($relation['class']) ? $relation['class']:$name;
                $alias = (isset($relation['alias']) && $relation['alias'] !== $relation['class']) ? ' as ' . $relation['alias'] : '';

                if ( ! isset($relation['type'])) {
                    $relation['type'] = Doctrine_Relation::ONE;
                }

                if ($relation['type'] === Doctrine_Relation::ONE ||
                    $relation['type'] === Doctrine_Relation::ONE_COMPOSITE) {
                    $ret[$i] = "    ".'$this->hasOne(\'' . $class . $alias . '\'';
                } else {
                    $ret[$i] = "    ".'$this->hasMany(\'' . $class . $alias . '\'';
                }

                $a = array();

                if (isset($relation['refClass'])) {
                    $a[] = '\'refClass\' => ' . var_export($relation['refClass'], true);
                }

                if (isset($relation['deferred']) && $relation['deferred']) {
                    $a[] = '\'default\' => ' . var_export($relation['deferred'], true);
                }

                if (isset($relation['local']) && $relation['local']) {
                    $a[] = '\'local\' => ' . var_export($relation['local'], true);
                }

                if (isset($relation['foreign']) && $relation['foreign']) {
                    $a[] = '\'foreign\' => ' . var_export($relation['foreign'], true);
                }

                if (isset($relation['onDelete']) && $relation['onDelete']) {
                    $a[] = '\'onDelete\' => ' . var_export($relation['onDelete'], true);
                }

                if (isset($relation['onUpdate']) && $relation['onUpdate']) {
                    $a[] = '\'onUpdate\' => ' . var_export($relation['onUpdate'], true);
                }

                if (isset($relation['equal']) && $relation['equal']) {
                    $a[] = '\'equal\' => ' . var_export($relation['equal'], true);
                }

                if ( ! empty($a)) {
                    $ret[$i] .= ', ' . 'array(';
                    $length = strlen($ret[$i]);
                    $ret[$i] .= implode(',' . PHP_EOL . str_repeat(' ', $length), $a) . ')';
                }

                $ret[$i] .= ');'."\n";
                $i++;
            }
        }

        if (isset($definition['templates']) && is_array($definition['templates']) && !empty($definition['templates'])) {
            $ret[$i] = $this->buildTemplates($definition['templates']);
            $i++;
        }

        if (isset($definition['actAs']) && is_array($definition['actAs']) && !empty($definition['actAs'])) {
            $ret[$i] = $this->buildActAs($definition['actAs']);
            $i++;
        }

        $code = implode("\n", $ret);
        $code = trim($code);

        if ($code) {
          return "\n  public function setUp()\n  {\n    ".$code."\n  }";
        }
    }

    /**
     * buildColumns
     *
     * @param string $array
     * @return void
     */
    public function buildColumns(array $columns)
    {
        $build = null;
        foreach ($columns as $name => $column) {
            $build .= "    ".'$this->hasColumn(\'' . $name . '\', \'' . $column['type'] . '\'';

            if ($column['length']) {
                $build .= ', ' . $column['length'];
            } else {
                $build .= ', null';
            }

            $options = $column;
            $unset = array('name', 'type', 'length', 'ptype');
            foreach ($options as $key => $value) {
                if (in_array($key, $unset) || $value === null) {
                    unset($options[$key]);
                }
            }

            if (is_array($options) && !empty($options)) {
                $build .= ', ' . var_export($options, true);
            }

            $build .= ");\n";
        }

        return $build;
    }

    /*
     * Build the accessors
     *
     * @param  string $table
     * @param  array  $columns
     */
    public function buildAccessors(array $definition)
    {
        $accessors = array();
        foreach (array_keys($definition['columns']) as $name) {
            $accessors[] = $name;
        }

        foreach ($definition['relations'] as $relation) {
            $accessors[] = $relation['alias'];
        }

        $ret = '';
        foreach ($accessors as $name) {
            // getters
            $ret .= "\n  public function get" . Doctrine_Inflector::classify(Doctrine_Inflector::tableize($name)) . "(\$load = true)\n";
            $ret .= "  {\n";
            $ret .= "    return \$this->get('{$name}', \$load);\n";
            $ret .= "  }\n";

            // setters
            $ret .= "\n  public function set" . Doctrine_Inflector::classify(Doctrine_Inflector::tableize($name)) . "(\${$name}, \$load = true)\n";
            $ret .= "  {\n";
            $ret .= "    return \$this->set('{$name}', \${$name}, \$load);\n";
            $ret .= "  }\n";
        }

        return $ret;
    }

    /**
     * buildTemplates
     *
     * @param string $array
     * @return void
     */
    public function buildTemplates(array $templates)
    {
        $build = '';
        foreach ($templates as $name => $options) {

            if (is_array($options) && !empty($options)) {
                $optionsPhp = var_export($options, true);

                $build .= "    \$this->loadTemplate('" . $name . "', " . $optionsPhp . ");\n";
            } else {
                if (isset($templates[0])) {
                    $build .= "    \$this->loadTemplate('" . $options . "');\n";
                } else {
                    $build .= "    \$this->loadTemplate('" . $name . "');\n";
                }
            }
        }

        return $build;
    }

    /**
     * buildActAs
     *
     * @param string $array
     * @return void
     */
    public function buildActAs(array $actAs)
    {
        $build = '';
        foreach ($actAs as $name => $options) {
            if (is_array($options) && !empty($options)) {
                $optionsPhp = var_export($options, true);

                $build .= "    \$this->actAs('" . $name . "', " . $optionsPhp . ");\n";
            } else {
                if (isset($actAs[0])) {
                    $build .= "    \$this->actAs('" . $options . "');\n";
                } else {
                    $build .= "    \$this->actAs('" . $name . "');\n";
                }
            }
        }

        return $build;
    }

    /**
     * buildAttributes
     *
     * @param string $array
     * @return void
     */
    public function buildAttributes(array $attributes)
    {
        $build = "\n";
        foreach ($attributes as $key => $value) {

            if (is_bool($value))
            {
              $values = $value ? 'true':'false';
            } else {
                if ( ! is_array($value)) {
                    $value = array($value);
                }

                $values = '';
                foreach ($value as $attr) {
                    $values .= "Doctrine::" . strtoupper($key) . "_" . strtoupper($attr) . ' ^ ';
                }

                // Trim last ^
                $values = substr($values, 0, strlen($values) - 3);
            }

            $build .= "    \$this->setAttribute(Doctrine::ATTR_" . strtoupper($key) . ", " . $values . ");\n";
        }

        return $build;
    }

    /**
     * buildTableOptions
     *
     * @param string $array
     * @return void
     */
    public function buildOptions(array $options)
    {
        $build = '';
        foreach ($options as $name => $value) {
            $build .= "    \$this->option('$name', " . var_export($value, true) . ");\n";
        }

        return $build;
    }

    /**
     * buildIndexes
     *
     * @param string $array
     * @return void
     */
    public function buildIndexes(array $indexes)
    {
      $build = '';

      foreach ($indexes as $indexName => $definitions) {
          $build .= "\n    \$this->index('" . $indexName . "'";
          $build .= ', ' . var_export($definitions, true);
          $build .= ');';
      }

      return $build;
    }

    /**
     * buildDefinition
     *
     * @param array $definition
     * @return string
     */
    public function buildDefinition(array $definition)
    {
        if ( ! isset($definition['className'])) {
            throw new Doctrine_Builder_Exception('Missing class name.');
        }

        $abstract = isset($definition['abstract']) && $definition['abstract'] === true ? 'abstract ':null;
        $className = $definition['className'];
        $extends = isset($definition['inheritance']['extends']) ? $definition['inheritance']['extends']:$this->_baseClassName;

        if ( ! (isset($definition['no_definition']) && $definition['no_definition'] === true)) {
            $tableDefinitionCode = $this->buildTableDefinition($definition);
            $setUpCode = $this->buildSetUp($definition);
        } else {
            $tableDefinitionCode = null;
            $setUpCode = null;
        }

        $accessorsCode = (isset($definition['generate_accessors']) && $definition['generate_accessors'] === true) ? $this->buildAccessors($definition):null;

        $content = sprintf(self::$_tpl, $abstract,
                                       $className,
                                       $extends,
                                       $tableDefinitionCode,
                                       $setUpCode,
                                       $accessorsCode);

        return $content;
    }

    /**
     * buildRecord
     *
     * @param array $options
     * @param array $columns
     * @param array $relations
     * @param array $indexes
     * @param array $attributes
     * @param array $templates
     * @param array $actAs
     * @return void=
     */
    public function buildRecord(array $definition)
    {
        if ( !isset($definition['className'])) {
            throw new Doctrine_Builder_Exception('Missing class name.');
        }

        if ($this->generateBaseClasses()) {
            $definition['is_package'] = (isset($definition['package']) && $definition['package']) ? true:false;

            if ($definition['is_package']) {
                $e = explode('.', $definition['package']);
                $definition['package_name'] = $e[0];
                unset($e[0]);

                $definition['package_path'] = implode(DIRECTORY_SEPARATOR, $e);
            }

            // Top level definition that extends from all the others
            $topLevel = $definition;
            unset($topLevel['tableName']);

            // If we have a package then we need to make this extend the package definition and not the base definition
            // The package definition will then extends the base definition
            $topLevel['inheritance']['extends'] = (isset($topLevel['package']) && $topLevel['package']) ? $this->_packagesPrefix . $topLevel['className']:'Base' . $topLevel['className'];
            $topLevel['no_definition'] = true;
            $topLevel['generate_once'] = true;
            $topLevel['is_main_class'] = true;
            unset($topLevel['connection']);

            // Package level definition that extends from the base definition
            if (isset($definition['package'])) {

                $packageLevel = $definition;
                $packageLevel['className'] = $topLevel['inheritance']['extends'];
                $packageLevel['inheritance']['extends'] = 'Base' . $topLevel['className'];
                $packageLevel['no_definition'] = true;
                $packageLevel['abstract'] = true;
                $packageLevel['override_parent'] = true;
                $packageLevel['generate_once'] = true;
                $packageLevel['is_package_class'] = true;
                unset($packageLevel['connection']);
            }

            $baseClass = $definition;
            $baseClass['className'] = 'Base' . $baseClass['className'];
            $baseClass['abstract'] = true;
            $baseClass['override_parent'] = false;
            $baseClass['is_base_class'] = true;

            $this->writeDefinition($baseClass);

            if (!empty($packageLevel)) {
                $this->writeDefinition($packageLevel);
            }

            $this->writeDefinition($topLevel);
        } else {
            $this->writeDefinition($definition);
        }
    }

    /**
     * writeDefinition
     *
     * @param array $options
     * @param array $columns
     * @param array $relations
     * @param array $indexes
     * @param array $attributes
     * @param array $templates
     * @param array $actAs
     * @return void
     */
    public function writeDefinition(array $definition)
    {
        $definitionCode = $this->buildDefinition($definition);

        $fileName = $definition['className'] . $this->_suffix;

        $packagesPath = $this->_packagesPath ? $this->_packagesPath:$this->_path;

        // If this is a main class that either extends from Base or Package class
        if (isset($definition['is_main_class']) && $definition['is_main_class']) {
            // If is package then we need to put it in a package subfolder
            if (isset($definition['is_package']) && $definition['is_package']) {
                $writePath = $this->_path . DIRECTORY_SEPARATOR . $definition['package_name'];
            // Otherwise lets just put it in the root of the path
            } else {
                $writePath = $this->_path;
            }
        }

        // If is the package class then we need to make the path to the complete package
        if (isset($definition['is_package_class']) && $definition['is_package_class']) {
            $path = str_replace('.', DIRECTORY_SEPARATOR, trim($definition['package']));

            $writePath = $packagesPath . DIRECTORY_SEPARATOR . $path;
        }

        // If it is the base class of the doctrine record definition
        if (isset($definition['is_base_class']) && $definition['is_base_class']) {
            // If it is a part of a package then we need to put it in a package subfolder
            if (isset($definition['is_package']) && $definition['is_package']) {
                $writePath  = $this->_path . DIRECTORY_SEPARATOR . $definition['package_name'] . DIRECTORY_SEPARATOR . $this->_baseClassesDirectory;
            // Otherwise lets just put it in the root generated folder
            } else {
                $writePath = $this->_path . DIRECTORY_SEPARATOR . $this->_baseClassesDirectory;
            }
        }

        if (isset($writePath)) {
            Doctrine_Lib::makeDirectories($writePath);

            $writePath .= DIRECTORY_SEPARATOR . $fileName;
        } else {
            Doctrine_Lib::makeDirectories($this->_path);

            $writePath = $this->_path . DIRECTORY_SEPARATOR . $fileName;
        }

        $code = "<?php" . PHP_EOL;

        if (isset($definition['connection']) && $definition['connection']) {
            $code .= "// Connection Component Binding\n";
            $code .= "Doctrine_Manager::getInstance()->bindComponent('" . $definition['connectionClassName'] . "', '" . $definition['connection'] . "');\n";
        }

        $code .= PHP_EOL . $definitionCode;

        if (isset($definition['generate_once']) && $definition['generate_once'] === true) {
            if (!file_exists($writePath)) {
                $bytes = file_put_contents($writePath, $code);
            }
        } else {
            $bytes = file_put_contents($writePath, $code);
        }

        if (isset($bytes) && $bytes === false) {
            throw new Doctrine_Builder_Exception("Couldn't write file " . $writePath);
        }
    }
}
