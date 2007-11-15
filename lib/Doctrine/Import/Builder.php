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
 * <http://www.phpdoctrine.com>.
 */

/**
 * Doctrine_Import_Builder
 *
 * Import builder is responsible of building Doctrine_Record classes
 * based on a database schema.
 *
 * @package     Doctrine
 * @subpackage  Import
 * @link        www.phpdoctrine.com
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Jukka Hassinen <Jukka.Hassinen@BrainAlliance.com>
 * @author      Nicolas Bérard-Nault <nicobn@php.net>
 * @author      Jonathan H. Wage <jwage@mac.com>
 */
class Doctrine_Import_Builder
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
     * generateTableClasses
     *
     * @var string
     */
    protected $_generateTableClasses = true;
    
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
        Doctrine::makeDirectories($path);
        
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
        Doctrine::makeDirectories($packagesPath);
        
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
     * generateTableClasses
     *
     * Specify whether or not to generate table classes which extend from Doctrine_Table
     *
     * @param string $bool
     * @return void
     */
    public function generateTableClasses($bool = null)
    {
        if ($bool !== null) {
            $this->_generateTableClasses = $bool;
        }

        return $this->_generateTableClasses;
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
     * Build the accessors
     *
     * @param  string $table
     * @param  array  $columns
     */
    public function buildAccessors(array $options, array $columns)
    {
        $ret = '';
        foreach ($columns as $name => $column) {
            // getters
            $ret .= "\n  public function get".Doctrine::classify($name)."(\$load = true)\n";
            $ret .= "  {\n";
            $ret .= "    return \$this->get('{$name}', \$load);\n";
            $ret .= "  }\n";

            // setters
            $ret .= "\n  public function set".Doctrine::classify($name)."(\${$name}, \$load = true)\n";
            $ret .= "  {\n";
            $ret .= "    return \$this->set('{$name}', \${$name}, \$load);\n";
            $ret .= "  }\n";
        }

        return $ret;
    }

    /*
     * Build the table definition of a Doctrine_Record object
     *
     * @param  string $table
     * @param  array  $tableColumns
     */
    public function buildTableDefinition(array $options, array $columns, array $relations, array $indexes, array $attributes, array $tableOptions)
    {
        $ret = array();
        
        $i = 0;
        
        if (isset($options['inheritance']['extends']) && !(isset($options['override_parent']) && $options['override_parent'] == false)) {
            $ret[$i] = "    parent::setTableDefinition();";
            $i++;
        }
        
        if (isset($options['tableName']) && !empty($options['tableName'])) {
            $ret[$i] = "    ".'$this->setTableName(\''. $options['tableName'].'\');';
            
            $i++;
        }
        
        foreach ($columns as $name => $column) {
            $ret[$i] = "    ".'$this->hasColumn(\'' . $name . '\', \'' . $column['type'] . '\'';
            
            if ($column['length']) {
                $ret[$i] .= ', ' . $column['length'];
            } else {
                $ret[$i] .= ', null';
            }

            $options = $column;
            $unset = array('name', 'type', 'length', 'ptype');
            foreach ($options as $key => $value) {
                if (in_array($key, $unset) || $value === null) {
                    unset($options[$key]);
                }
            }
            
            $ret[$i] .= ', ' . var_export($options, true);
            
            $ret[$i] .= ');';

            if ($i < (count($columns) - 1)) {
                $ret[$i] .= PHP_EOL;
            }
            $i++;
        }
        
        $ret[$i] = $this->buildIndexes($indexes);
        $i++;
        
        $ret[$i] = $this->buildAttributes($attributes);
        $i++;
        
        $ret[$i] = $this->buildTableOptions($tableOptions);
        
        $code = implode("\n", $ret);
        $code = trim($code);
        
        if ($code) {
          return "\n  public function setTableDefinition()"."\n  {\n    ".$code."\n  }";
        }
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
    public function buildTableOptions(array $options)
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
     * buildSetUp
     *
     * @param  array $options 
     * @param  array $columns 
     * @param  array $relations 
     * @return string
     */
    public function buildSetUp(array $options, array $columns, array $relations, array $templates, array $actAs)
    {
        $ret = array();
        $i = 0;
        
        if (isset($options['inheritance']['extends']) && !(isset($options['override_parent']) && $options['override_parent'] == false)) {
            $ret[$i] = "    parent::setUp();";
            $i++;
        }
        
        foreach ($relations as $name => $relation) {
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
        
        if (isset($options['inheritance']['keyField']) && isset($options['inheritance']['keyValue'])) {
            $i++;
            $ret[$i] = "    ".'$this->setInheritanceMap(array(\''.$options['inheritance']['keyField'].'\' => \''.$options['inheritance']['keyValue'].'\'));';
        }
        
        $ret[$i] = $this->buildTemplates($templates);
        $i++;
        
        $ret[$i] = $this->buildActAs($actAs);
        $i++;
        
        $code = implode("\n", $ret);
        $code = trim($code);
        
        if ($code) {
          return "\n  public function setUp()\n  {\n    ".$code."\n  }";
        }
    }

    /**
     * buildDefinition
     *
     * @param array $options 
     * @param array $columns 
     * @param array $relations 
     * @param array $indexes 
     * @param array $attributes 
     * @param array $templates 
     * @param array $actAs 
     * @return string
     */
    public function buildDefinition(array $options, array $columns, array $relations = array(), array $indexes = array(), $attributes = array(), array $templates = array(), array $actAs = array(), array $tableOptions = array())
    {
        if ( ! isset($options['className'])) {
            throw new Doctrine_Import_Builder_Exception('Missing class name.');
        }

        $abstract = isset($options['abstract']) && $options['abstract'] === true ? 'abstract ':null;
        $className = $options['className'];
        $extends = isset($options['inheritance']['extends']) ? $options['inheritance']['extends']:$this->_baseClassName;

        if ( ! (isset($options['no_definition']) && $options['no_definition'] === true)) {
            $definition = $this->buildTableDefinition($options, $columns, $relations, $indexes, $attributes, $tableOptions);
            $setUp = $this->buildSetUp($options, $columns, $relations, $templates, $actAs);
        } else {
            $definition = null;
            $setUp = null;
        }
        
        $accessors = (isset($options['generate_accessors']) && $options['generate_accessors'] === true) ? $this->buildAccessors($options, $columns):null;
        
        $content = sprintf(self::$_tpl, $abstract,
                                       $className,
                                       $extends,
                                       $definition,
                                       $setUp,
                                       $accessors);
        
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
    public function buildRecord(array $options, array $columns, array $relations = array(), array $indexes = array(), array $attributes = array(), array $templates = array(), array $actAs = array(), array $tableOptions = array())
    {
        if ( !isset($options['className'])) {
            throw new Doctrine_Import_Builder_Exception('Missing class name.');
        }

        if ($this->generateBaseClasses()) {
            $options['is_package'] = (isset($options['package']) && $options['package']) ? true:false;
            
            if ($options['is_package']) {
                $e = explode('.', $options['package']);
                $options['package_name'] = $e[0];
                unset($e[0]);
                
                $options['package_path'] = implode(DIRECTORY_SEPARATOR, $e);
            }
            
            // Top level definition that extends from all the others
            $topLevel = $options;
            unset($topLevel['tableName']);
            
            // If we have a package then we need to make this extend the package definition and not the base definition
            // The package definition will then extends the base definition
            $topLevel['inheritance']['extends'] = (isset($topLevel['package']) && $topLevel['package']) ? $this->_packagesPrefix . $topLevel['className']:'Base' . $topLevel['className'];
            $topLevel['no_definition'] = true;
            $topLevel['generate_once'] = true;
            $topLevel['is_main_class'] = true;

            // Package level definition that extends from the base definition
            if (isset($options['package'])) {
                
                $packageLevel = $options;
                $packageLevel['className'] = $topLevel['inheritance']['extends'];
                $packageLevel['inheritance']['extends'] = 'Base' . $topLevel['className'];
                $packageLevel['no_definition'] = true;
                $packageLevel['abstract'] = true;
                $packageLevel['override_parent'] = true;
                $packageLevel['generate_once'] = true;
                $packageLevel['is_package_class'] = true;
            }

            $baseClass = $options;
            $baseClass['className'] = 'Base' . $baseClass['className'];
            $baseClass['abstract'] = true;
            $baseClass['override_parent'] = true;
            $baseClass['is_base_class'] = true;

            $this->writeDefinition($baseClass, $columns, $relations, $indexes, $attributes, $templates, $actAs, $tableOptions);
            
            if (!empty($packageLevel)) {
                $this->writeDefinition($packageLevel);
            }
            
            $this->writeDefinition($topLevel);
        } else {
            $this->writeDefinition($options, $columns, $relations, $indexes, $attributes, $templates, $actAs, $tableOptions);
        }
    }
    
    /**
     * writeTableDefinition
     *
     * @return void
     */
    public function writeTableDefinition($className, $path, $options = array())
    {
        $className = $className . 'Table';
        
        $content  = '<?php' . PHP_EOL;
        $content .= sprintf(self::$_tpl, false,
                                       $className,
                                       isset($options['extends']) ? $options['extends']:'Doctrine_Table',
                                       null,
                                       null,
                                       null
                                       );
        
        Doctrine::makeDirectories($path);
        
        $writePath = $path . DIRECTORY_SEPARATOR . $className . $this->_suffix;
        
        if (!file_exists($writePath)) {
            file_put_contents($writePath, $content);
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
    public function writeDefinition(array $options, array $columns = array(), array $relations = array(), array $indexes = array(), array $attributes = array(), array $templates = array(), array $actAs = array(), array $tableOptions = array())
    {
        $definition = $this->buildDefinition($options, $columns, $relations, $indexes, $attributes, $templates, $actAs, $tableOptions);

        $fileName = $options['className'] . $this->_suffix;

        $packagesPath = $this->_packagesPath ? $this->_packagesPath:$this->_path;

        // If this is a main class that either extends from Base or Package class
        if (isset($options['is_main_class']) && $options['is_main_class']) {
            // If is package then we need to put it in a package subfolder
            if (isset($options['is_package']) && $options['is_package']) {
                $writePath = $this->_path . DIRECTORY_SEPARATOR . $options['package_name'];
                
                $this->writeTableDefinition($options['className'], $writePath, array('extends' => $options['inheritance']['extends'] . 'Table'));
            // Otherwise lets just put it in the root of the path
            } else {
                $writePath = $this->_path;
                
                $this->writeTableDefinition($options['className'], $writePath);
            }
        }

        // If is the package class then we need to make the path to the complete package
        if (isset($options['is_package_class']) && $options['is_package_class']) {
            $path = str_replace('.', DIRECTORY_SEPARATOR, trim($options['package']));
            
            $writePath = $packagesPath . DIRECTORY_SEPARATOR . $path;
            
            $this->writeTableDefinition($options['className'], $writePath);
        }
        
        // If it is the base class of the doctrine record definition
        if (isset($options['is_base_class']) && $options['is_base_class']) {
            // If it is a part of a package then we need to put it in a package subfolder
            if (isset($options['is_package']) && $options['is_package']) {
                $writePath  = $this->_path . DIRECTORY_SEPARATOR . $options['package_name'] . DIRECTORY_SEPARATOR . $this->_baseClassesDirectory;
            // Otherwise lets just put it in the root generated folder
            } else {
                $writePath = $this->_path . DIRECTORY_SEPARATOR . $this->_baseClassesDirectory;
            }
        }
        
        if (isset($writePath)) {
            Doctrine::makeDirectories($writePath);
            
            $writePath .= DIRECTORY_SEPARATOR . $fileName;
        } else {
            Doctrine::makeDirectories($this->_path);
            
            $writePath = $this->_path . DIRECTORY_SEPARATOR . $fileName;
        }
        
        $code = "<?php" . PHP_EOL;
        
        if (isset($options['requires'])) {
            if ( ! is_array($options['requires'])) {
                $options['requires'] = array($options['requires']);
            }
            
            foreach ($options['requires'] as $require) {
                $code .= "require_once('".$require."');\n";
            }
        }
        
        if (isset($options['connection']) && $options['connection']) {
            $code .= "// Connection Component Binding\n";
            $code .= "Doctrine_Manager::getInstance()->bindComponent('" . $options['connectionClassName'] . "', '" . $options['connection'] . "');\n";
        }

        $code .= PHP_EOL . $definition;

        if (isset($options['generate_once']) && $options['generate_once'] === true) {
            if (!file_exists($writePath)) {
                $bytes = file_put_contents($writePath, $code);
            }
        } else {
            $bytes = file_put_contents($writePath, $code);
        }

        if (isset($bytes) && $bytes === false) {
            throw new Doctrine_Import_Builder_Exception("Couldn't write file " . $writePath);
        }
    }
}
