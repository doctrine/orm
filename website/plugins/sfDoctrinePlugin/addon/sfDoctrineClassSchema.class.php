<?php
/*
 * This file is part of the sfDoctrine package.
 * (c) 2006-2007 Olivier Verdier <Olivier.Verdier@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @package    symfony.plugins
 * @subpackage sfDoctrine
 * @author     Olivier Verdier <Olivier.Verdier@gmail.com>
 * @version    SVN: $Id: sfDoctrineClassSchema.class.php 4696 2007-07-20 17:04:44Z gnat $
 */

class sfDoctrineClassSchema
{
  // the table associated to this class
  protected $table;

  // class name
  protected $phpName;

  // list of columns
  protected $columns = array();

  // list of relations (foreign keys) linking to this class
  protected $many = array();

  // inheritance description
  protected $inheritance = array();

  // i18n description
  protected $i18n = array();

  // indexes
  protected $indexes = array();
  
  // Uniques
    protected $uniques = array();

  // options
  protected $options = array();

  public function __construct($name, array $cd = array())
  {
    $this->setPhpName($name);

    // elementary key verification
    $illegalKeys = array_diff_key($cd, array_flip(array('columns', 'tableName', 'inheritance', 'i18n', 'indexes', 'uniques', 'options')));

    if ($illegalKeys)
      throw new sfDoctrineSchemaException(sprintf('Invalid key "%s" in description of class "%s"', array_shift(array_keys($illegalKeys)), $name));

    if (isset($cd['inheritance']))
    {
      $this->setInheritance($cd['inheritance']);
    }
    
    // set i18n
    if (isset($cd['i18n']))
      $this->setI18n($cd['i18n']);
    
    // add indexes
    if (isset($cd['indexes']))
      $this->addIndexes($cd['indexes']);

    // add uniques
    if (isset($cd['uniques']))
      $this->addUniques($cd['uniques']);
      
    // add options
    if (isset($cd['options']))
      $this->addOptions($cd['options']);

    // add columns
    if (isset($cd['columns']))
      foreach ($cd['columns'] as $colName => $column)
      {
        $docCol = new sfDoctrineColumnSchema($colName, $column);
        $this->addColumn($docCol);
      }
  }

  // add a column if none with the same name is already there
  public function addColumn($docCol)
  {
    if (isset($this->columns[$docCol->getName()]))
      return;

    // sets up the possible relation for that column
    $docCol->setUpForeignRelation($this->getPhpName());

    $this->columns[$docCol->getName()] = $docCol;
  }

  public function getColumns()
  {
    return $this->columns;
  }

  // for testing only
  public function getColumn($colName)
  {
    if (!isset($this->columns[$colName]))
      throw new sfDoctrineSchemaException(sprintf('Column "%s" is not defined', $colName));
    return $this->columns[$colName];
  }

  public function addToMany($relation)
  {
    $this->many[] = $relation;
  }

  public function getPhpName()
  {
    return $this->phpName;
  }

  public function setPhpName($newName)
  {
    $this->phpName = $newName;
  }

  public function setTable($table)
  {
    $this->table = $table;
  }

  public function getTable()
  {
    if (!$this->hasTable())
      throw new sfDoctrineSchemaException(sprintf('Table not defined for class "%s"', $this->getPhpName()));
    return $this->table;
  }

  public function getTableName()
  {
    return $this->getTable()->getName();
  }

  public function hasTable()
  {
    return isset($this->table);
  }

  public function setI18n($i18n)
  {
    $check = array('class', 'cultureField');
    foreach ($check as $key)
      if (!isset($i18n[$key]))
        throw new sfDoctrineSchemaException(sprintf('The key "%s" is missing from the i18n information for class "%s".', $key, $this->getPhpName()));
    $this->i18n = $i18n;
  }

  public function hasI18n()
  {
    return !empty($this->i18n);
  }

  public function getI18n($key)
  {
    return $this->i18n[$key];
  }

  public function setInheritance($inh)
  {
    $check = array('extends');
    if (isset($inh['keyField']) || isset($inh['keyValue']))
      $check = array_merge($check, array('keyField', 'keyValue'));
    elseif (isset($inh['keyFields']))
      $check = array_merge($check, array('keyFields'));

    foreach ($check as $key)
      if (!isset($inh[$key]))
        throw new sfDoctrineSchemaException(sprintf('The key "%s" is missing from the inheritance information for class "%s".', $key, $this->getPhpName()));
    $this->inheritance = $inh;
  }

  public function getInheritance()
  {
    return $this->inheritance;
  }

  public function hasOneTableInheritance()
  {
    if ($inh = $this->inheritance)
      if (isset($inh['keyValue']) || isset($inh['keyFields']))
        return true;
    return false;
  }

  public function getOptions()
  {
      return $this->options;
  }

  public function addOptions($options)
  {
      $this->options = $options;
  }

  public function hasOptions()
  {
      return count($this->options) ? true : false;
  }

  public function getIndexes()
  {
      return $this->indexes;
  }

  public function addIndexes($indexes)
  {
      $this->indexes = $indexes;
  }

  public function hasIndexes()
  {
    return count($this->indexes) ? true : false;
  }
  
  public function addUniques($uniques)
    {
        $this->uniques = $uniques;
    }

    public function hasUniques()
    {
      return count($this->uniques) ? true : false;
    }
  
  

  public function getParentClassName()
  {
    return $this->inheritance['extends'];
  }

  // generates the name of the generated class
  public function basePhpName($name = null)
  {
    if (!$name)
      $name = $this->getPhpName();
    return 'Base'.$name;
  }

  // outputs a function in php
  public static function outputFunction($functionName, $contents, $phpdoc = '')
  {
    if (is_array($contents))
      $contents = implode("\n    ", $contents);
    return "
  $phpdoc
  public function $functionName()
  {
    $contents
  }
  ";
  }

  // output a class in php
  public static function outputClass($className, $extends, $contents, $phpdoc = '')
  {
    $signature = sprintf("auto-generated by the sfDoctrine plugin");
    return "<?php
/*
 * $phpdoc
 *
 * $signature
 */
class $className extends $extends
{
  $contents
}
";
  }

  public function getRelation($columnName)
  {
    return $this->columns[$columnName]->getRelation();
  }

  // this function returns an array ('className'=><class name>, 'source'=><class file contents>, 'base'=>true/false) of PHP classes
  // corresponding to this class
  public function asPHP()
  {
    $classes = array();

    // main base class
    $out = array();

    $tableDef = array();
    $setup = array();

    // if that class inherits from another we call the parent methods
    if ($this->inheritance)
    {
      $tableDef[] = "parent::setTableDefinition();\n";
      $setup[] = "parent::setUp();\n";
    }

    // if it is a table we define the table name
    if ($this->hasTable())
    {
      $tableDef[] = "\$this->setTableName('{$this->getTableName()}');\n"; 
    }

    foreach ($this->columns as $column)
    {
      $args = array();

      $tableDef[] = $column->asPhp();
    }

    // declare indexes if any
    foreach ($this->indexes as $name => $value)
    {
      // only write option if value is set
      if(!empty($value))
      {
        $valueExport = is_array($value) ? var_export($value, true) : "'$value'";
        $tableDef[] = "\$this->index('$name', $valueExport);";
      }
    }
    
    // declare uniques if any
    foreach ($this->uniques as $name => $value)
    {
      // only write option if value is set
      if(!empty($value))
      {
        $valueExport = is_array($value) ? var_export($value, true) : "'$value'";
        $tableDef[] = "\$this->unique('$name', $valueExport);";
      }
    }

    foreach ($this->options as $name => $value)
    {
      // only write option if value is set
      if(!empty($value))
      {
        $valueExport = is_array($value) ? var_export($value, true) : "'$value'";
        $tableDef[] = "\$this->option('$name', $valueExport);";          
      }
    }

    $out[] = self::outputFunction('setTableDefinition', $tableDef);

    // has/own one
    foreach($this->columns as $col)
      if ($rel = $col->getRelation())
      {
        $setup[] = $rel->asOnePhp();
      }

    // has/own many
    foreach($this->many as $rel)
    {
      $setup[] = $rel->asManyPhp();
    }

    // declare inheritance if needed
    if ($this->hasOneTableInheritance())
    {
      $inh = $this->getInheritance();
      if (isset($inh['keyFields']))
      {
        $keyFields = $inh['keyFields'];
        $keyFields = is_array($keyFields) ? $keyFields : array($keyFields);
      }
      else
        $keyFields = array($inh['keyField'] => $inh['keyValue']);

      $setup[] = '$this->setInheritanceMap('.var_export($keyFields, true).');';
    }

    // declare i18n if any
    if ($this->hasI18n())
      $setup[] = "\$this->hasI18nTable('{$this->getI18n('class')}', '{$this->getI18n('cultureField')}');";

    $out[] = self::outputFunction('setUp', $setup);

    // the following could also be: if ($this->inheritance)
    // FIXME: create a global class!
    if (isset($this->inheritance['extends']))
      $parentName = $this->inheritance['extends'];
    else
      $parentName = ($this->hasI18n() ? 'sfDoctrineRecordI18n' : 'sfDoctrineRecord');


    $class = array
    (
      'name' => $this->getPhpName(), // name of the child class; used only internally
      'className' => $this->basePHPName(),
      'source' => self::outputClass($this->basePHPName(), $parentName, implode("\n", $out), 'Base class; DO NOT EDIT'),
      'overwrite' => true, // carful! even this set to false will overwrite!!!
    );

    $classes[] = $class;

    $package = $this->getTable()->getPackage();

    // generate the empty user and table classes
    foreach ($classes as $baseClass)
    {
      $name = $baseClass['name'];
      $parentClass = $baseClass['className'];

      $tableName = $name.'Table'; // convention imposed by Doctrine
      if (isset($this->inheritance['extends']))
        $parentTable = $this->inheritance['extends'].'Table';
      else
        $parentTable = 'Doctrine_Table';

      if ($package)
      {
        $pluginClassName = 'Plugin'.$name;
        $classes[] = array
        (
          'className'=> $pluginClassName,
          'source' => self::outputClass($pluginClassName, $parentClass, '', 'Plugin class'),
          'plugin' => true,
        );
        // we hook the plugin class name in
        $parentClass = $pluginClassName;

        // same for tables
        $pluginTableName = 'Plugin'.$tableName;
        $classes[] = array
        (
          'className' => $pluginTableName,
          'source' => self::outputClass($pluginTableName, $parentTable, '', 'Plugin table'),
          'plugin' => true,
        );
        $parentTable = $pluginTableName;
      }

      $classes[] = array
      (
        'className'=>$name, 
        'source'=>self::outputClass($name, $parentClass, '', 'Edit this file to customise your model class'), 
      );

      $classes[] = array
      (
        'className'=>$tableName,
        'source'=>self::outputClass($tableName, $parentTable, '', 'Edit this file to customise your model table'),
      );

    }


    return $classes;
  }

  // outputs a nested array
  public function asDoctrineYml()
  {
    $output = array();

    if ($this->inheritance)
      $output['inheritance'] = $this->inheritance;
    else
      $output['tableName'] = $this->getTableName();

    $cols = array();
    foreach ($this->columns as $col)
    {
      $cols[$col->getName()] = $col->asDoctrineYml();
    }

    $output['columns'] = $cols;

    return $output;
  }

  // outputs the columns of that class in propel xml format
  public function addPropelXmlColumns(&$table)
  {
    // we add the id column which is automatically created in doctrine
    $this->addColumn(new sfDoctrineColumnSchema('id', array('type'=>'integer', 'size'=>10, 'primary'=>true, 'autoincrement'=>true)));
    foreach($this->columns as $col)
    {
      $col->addPropelXml($table);
    }
  }

  public function debug()
  {
    $debug = array();
    $debug['inheritance'] = $this->inheritance;
    $debug['many'] = $this->many;
    $debug['i18n'] = $this->i18n;
    foreach ($this->columns as $col)
    {
      $debug['columns'][$col->getName()] = $col->debug();
    }
    return $debug;
  }
}
