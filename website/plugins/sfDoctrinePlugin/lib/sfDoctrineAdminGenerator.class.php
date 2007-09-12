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
 * @version    SVN: $Id: sfDoctrineAdminGenerator.class.php 4533 2007-07-03 23:36:10Z gnat $
 */

class sfDoctrineAdminGenerator extends sfAdminGenerator
{

  protected $table;

  public function initialize($generatorManager)
  {
    // otherwise the class never gets loaded... don't ask me why...
    include_once(sfConfig::get('sf_symfony_lib_dir').'/vendor/creole/CreoleTypes.php');
    parent::initialize($generatorManager);

    $this->setGeneratorClass('sfDoctrineAdmin');
  }

  protected function loadMapBuilderClasses()
  {
    $conn = Doctrine_Manager::getInstance()->openConnection('mock://no-one@localhost/empty', null, false);
    $this->table = $conn->getTable($this->getClassName());
  }

  protected function getTable()
  {
    return $this->table;
  }

  protected function loadPrimaryKeys()
  {
    foreach ($this->getTable()->getPrimaryKeys() as $primaryKey)
      $this->primaryKey[] = new sfDoctrineAdminColumn($primaryKey);
    // FIXME: check that there is at least one primary key
  }

  public function getColumns($paramName, $category='NONE')
  {

    $columns = parent::getColumns($paramName, $category);    

    // set the foreign key indicator
    $relations = $this->getTable()->getRelations();

    $cols = $this->getTable()->getColumns();

    foreach ($columns as $index => $column)
    {
      if (isset($relations[$column->getName()]))
      {
        $fkcolumn = $relations[$column->getName()];
        $columnName = $relations[$column->getName()]->getLocal();
        if ($columnName != 'id') // i don't know why this is necessary
        {
          $column->setRelatedClassName($fkcolumn->getTable()->getComponentName());
          $column->setColumnName($columnName);

          if (isset($cols[$columnName])) // if it is not a many2many
            $column->setColumnInfo($cols[$columnName]);

          $columns[$index] = $column;
        }
      }
    }

    return $columns;
  }

  function getAllColumns()
  {
    $cols = $this->getTable()->getColumns();
    $rels = $this->getTable()->getRelations();
    $columns = array();
    foreach ($cols as $name => $col)
    {
      // we set out to replace the foreign key to their corresponding aliases
      $found = null;
      foreach ($rels as $alias=>$rel)
      {
        $relType = $rel->getType();
        if ($rel->getLocal() == $name && $relType != Doctrine_Relation::MANY_AGGREGATE && $relType != Doctrine_Relation::MANY_COMPOSITE)
          $found = $alias;
      }
      if ($found)
      {
        $name = $found;
      }
      $columns[] = new sfDoctrineAdminColumn($name, $col);
    }    
    return $columns;
  }

  function getAdminColumnForField($field, $flag = null)
  {
    $cols = $this->getTable()->getColumns(); // put this in an internal variable?
    return  new sfDoctrineAdminColumn($field, (isset($cols[$field]) ? $cols[$field] : null), $flag);
  }


  function getPHPObjectHelper($helperName, $column, $params, $localParams = array())
  {
    $params = $this->getObjectTagParams($params, $localParams);

    // special treatment for object_select_tag:
    if ($helperName == 'select_tag')
    {
      $column = new sfDoctrineAdminColumn($column->getColumnName(), null, null);
    }
    return sprintf ('object_%s($%s, %s, %s)', $helperName, $this->getSingularName(), var_export($this->getColumnGetter($column), true), $params);
  }

  function getColumnGetter($column, $developed = false, $prefix = '')
  {
    if ($developed)
      return sprintf("$%s%s->get('%s')", $prefix, $this->getSingularName(), $column->getName());
    // no parenthesis, we return a method+parameters array
    return array('get', array($column->getName()));
  }

  function getColumnSetter($column, $value, $singleQuotes = false, $prefix = 'this->')
  {
    if ($singleQuotes)
      $value = sprintf("'%s'", $value);
    return sprintf('$%s%s->set(\'%s\', %s)', $prefix, $this->getSingularName(), $column->getName(), $value);
  }

  function getRelatedClassName($column)
  {
    return $column->getRelatedClassName();
  }

  public function getColumnEditTag($column, $params = array())
  {
    if ($column->getDoctrineType() == 'enum')
    {
      // FIXME: this is called already in the sfAdminGenerator class!!!
      $params = array_merge(array('control_name' => $this->getSingularName().'['.$column->getName().']'), $params);

      $values = $this->getTable()->getEnumValues($column->getName());
      $params = array_merge(array('enumValues'=>$values), $params);
      return $this->getPHPObjectHelper('enum_tag', $column, $params);
    }
    return parent::getColumnEditTag($column, $params);
  }

}

class sfDoctrineAdminColumn extends sfAdminColumn
{
  // doctrine to creole type conversion
  static $docToCreole = array(
    'boolean'   => CreoleTypes::BOOLEAN,
    'string'    => CreoleTypes::TEXT, 
    'integer'   => CreoleTypes::INTEGER,
    'date'      => CreoleTypes::DATE, 
    'timestamp' => CreoleTypes::TIMESTAMP,
    'time'      => CreoleTypes::TIME,
    'enum'      => CreoleTypes::TINYINT,
    'float'     => CreoleTypes::FLOAT,
    'double'    => CreoleTypes::FLOAT,
    'clob'      => CreoleTypes::CLOB,
    'blob'      => CreoleTypes::BLOB,
    'object'    => CreoleTypes::VARCHAR,
    'array'     => CreoleTypes::VARCHAR,
    'decimal'	=> CreoleTypes::DECIMAL,
  );

  protected $relatedClassName = null;
  protected $name = null;
  protected $columnName; // stores the real foreign id column

  function getDoctrineType()
  {
    return isset($this->column['type']) ? $this->column['type'] : null;
  }

  function getCreoleType()
  { 
    $dType = $this->getDoctrineType();

    // we simulate the CHAR/VARCHAR types to generate input_tags
    if(($dType == 'string') and ($this->getSize() < 256))
    {
      return CreoleTypes::VARCHAR;
    }

    return $dType ? self::$docToCreole[$dType] : CreoleTypes::OTHER;
  }

  function getSize()
  {
    return $this->column['length'];
  }

  function isNotNull()
  {
    //FIXME THIS NEEDS TO BE UPDATE-but I don't know the format for the column array
    if (isset($this->column[2]['notnull']))
      return $this->column[2]['notnull'];
    return false;
  }

  function isPrimaryKey()
  {
    if (isset($this->column['primary']))
      return $this->column['primary'];
    return false;
  }

  function setRelatedClassName($newName)
  {
    $this->relatedClassName = $newName;
  }

  function getRelatedClassName()
  {
    return $this->relatedClassName;
  }

  function setColumnName($newName)
  {
    $this->columnName = $newName;
  }

  function getColumnName()
  {
    return $this->columnName;
  }

  function setColumnInfo($col)
  {
    $this->column = $col;
  }

  // FIXME: this method is never used... remove it?
  function setName($newName)
  {
    $this->name = $newName;
  }

  function getName()
  {
    if (isset($this->name))
    {
      return $this->name;
    }
    // a bit kludgy: the field name is actually in $this->phpName
    return parent::getPhpName();
  }

  function isForeignKey()
  {
    return isset($this->relatedClassName);
  }

  // all the calls that were forwarded to the table object with propel
  // have to be dealt with explicitly here, otherwise:  
  public function __call($name, $arguments)
  {
    throw new Exception(sprintf('Unhandled call: "%s"', $name));
  }
}

