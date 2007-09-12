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
 * @version    SVN: $Id: sfDoctrineColumnSchema.class.php 4084 2007-05-23 09:48:50Z chtito $
 */

/*
  This class stores information about a column in two arrays:
  - properties: contains the name, type, size and constraints
  - columnInfo: contains also the foreign relation information
*/
class sfDoctrineColumnSchema
{
  protected static $propel2docDictionary = array(
  'types'=> array(
    'tinyint' => 'integer',
    'smallint' => 'integer',
    'bigint' => 'integer',
    'real' => 'float',
    'decimal' => 'float',
    'char' => 'string',
    'varchar' => 'string',
    'longvarchar' => 'string',
#    'smallint'=> 'enum', // enums are converted to smallints
#    'blob' => 'array', // arrays will be blobs
#    'integer' => 'integer', // to convert doc integer to integer
/*
    'double' => 'double',
    'float' => 'float',
    'boolean' => 'boolean',
    'date' => 'date',
    'time' => 'timestamp',
    'timestamp' => 'timestamp',
    'blob' => 'blob',
    'clob' => 'clob'
    */
  ),
  'constraints' => array('autoIncrement' => 'autoincrement', 'primaryKey' => 'primary')
  );

  //FIXME: double, float,real???
  protected static $defaultPropelSize = array(
    'tinyint' => 3,
    'smallint' => 5,
    'integer' => 11,
    'bigint' => 20,
    'longvarchar'=>4000,
  );

  protected static $defaultDoctrineSize = array(
    'string'=> 4000,
    'integer' => 10,
    'double' => 10,
    'float' => 10,
    'enum' => 2,
    'array' => 100,
  );

  static $allowedConstraints = array('primary', 'autoincrement', 'default', 'enum', 'unique', 'nospace', 'notblank', 'notnull', 'email', 'scale', 'zerofill');

  // column properties: name, size, type and constraints
  protected $properties;

  // column name
  protected $name;

// temporary storage of the description array; used when the class sets up the relation
  protected $columnInfo; 

  // set if the column is a foreign key
  protected $relation = null;

  // we essentially set up the properties array
  // and translate from propel if needed
  public function __construct($colName, $columnDescription = array(), $translatePropel = false)
  {
    // sometimes we get null if the yml line is empty
    if ($columnDescription == null)
      $columnDescription = array();

    // for the short syntax type(size)
    if (is_string($columnDescription))
      $columnDescription = array('type'=>$columnDescription);

    $this->setName($colName);

    $columnInfo = new sfParameterHolder();
    $columnInfo->add($columnDescription);

    if ($translatePropel)
    {
      // we translate the propel types to doctrine ones
      $propelType = strtolower($columnInfo->get('type'));
      if (array_key_exists($propelType, self::$propel2docDictionary['types']))
        $columnInfo->set('type', self::$propel2docDictionary['types'][$propelType]);
      else
        $columnInfo->set('type', $propelType); // we store it in lowercase

      // if there is a default propel size we set it
      if (!$columnInfo->get('size'))
        if (isset(self::$defaultPropelSize[$propelType]))
          $columnInfo->set('size', self::$defaultPropelSize[$propelType]);

      // we translate the constraints
      foreach ($columnInfo->getAll() as $key=>$value)
      {
        if (array_key_exists($key, self::$propel2docDictionary['constraints']))
          $columnInfo->set(self::$propel2docDictionary['constraints'][$key], $columnInfo->get($key));
      }
    }

    // we store the raw description, only used in setUpForeignRelation
    $this->columnInfo = $columnInfo;


    // name
    $this->setProperty('name', $colName);
    $this->setProperty('columnName', $columnInfo->get('columnName'));

    // type
    if (!($type = $columnInfo->get('type')))
    {
      // we try to figure out the type
      // FIXME: write a method to detect relations?
      if ($columnInfo->get('foreignClass') || $columnInfo->get('foreignTable')) 
        $type = 'integer'; // foreign key
      else
        $type = 'string'; // default type
    }
    elseif(is_string($type))  // we check for the short syntax type
    {
      preg_match('/([^\(\s]+)\s*\([\s]*([\d]+)[\s]*\)/', $type, $matches);
      if (!empty($matches))
      {
        $type = $matches[1];
        $columnInfo->set('size', $matches[2]);
      }
    }
    $this->setProperty('type', $type);

    // size
    if (!($size = $columnInfo->get('size')))
    {
      if (is_string($type))
      {
        if (isset(self::$defaultDoctrineSize[$type]))
        $size = self::$defaultDoctrineSize[$type]; // we have a default size for this type
      }
    }
    if (!$size)
      $size = 'null';


    $this->setProperty('size', $size);

    // constraints
    if ($constraints = array_intersect_key($columnDescription, array_flip(self::$allowedConstraints)))
      $this->properties = array_merge($this->properties, $constraints);
  }


  // FIXME: simplify this function
  public function setUpForeignRelation($className)
  {
    $colInfo = $this->getColumnInfo();
    $colName = $this->getName();

    // If there is no relation info for this column
    if (!$colInfo->has('foreignTable') && !$colInfo->has('foreignClass'))
      return;

    $foreignClass = $colInfo->get('foreignClass');

    // if the localName (plural name) is not specified, we add an "s" 
    // as propel does
    $localName = $colInfo->get('localName', $className.'s');
    $foreignTable = $colInfo->get('foreignTable');

    $foreignName = $colInfo->get('foreignName', null);

    $fr = $colInfo->get('foreignReference', 'id');

    $counterpart = $colInfo->get('counterpart');

    $relationInfo = array
    (
      'localReference'=>$colName, 
      'foreignReference'=>$fr, 
      'localName'=>$localName, 
      'foreignName'=>$foreignName, 
      'counterpart' => $counterpart, 
      'foreignClass'=>$foreignClass, 
      'foreignTable'=>$foreignTable, // used only for propel import
      'localClass'=>$className,
      'options'=>$colInfo, // the remaining relation options
    );

    $this->relation = new sfDoctrineRelationSchema($relationInfo);
  }

  public function getColumnInfo()
  {
    return $this->columnInfo;
  }

  public function setName($name)
  {
    $this->name = $name;
  }

  public function getName()
  {
    return $this->name;
  }

  public function getRelation()
  {
    return $this->relation;
  }

  public function hasRelation()
  {
    return isset($this->relation);
  }

  public function setProperty($name, $value)
  {
    $this->properties[$name] = $value;
  }

  public function getProperty($name)
  {
    return $this->properties[$name];
  }

  public function getProperties()
  {
    return $this->properties;
  }

  protected function niceVarExport($array)
  {
    return str_replace(array("\n"), array(''), var_export($array, 1));
  }

  static protected $doctrineArgs = array('name' => false, 'type' => true, 'size' => false);

  // generates the doctrine description of a column in PHP
  public function asPhp()
  {
    $props = $this->getProperties();

    $args = array();

    // take care of the enum type
    // FIXME: remove this "trick" some day?
    if (is_array($props['type']))
    {
      $props['values'] = $props['type'];
      $props['type'] = 'enum';
    }

    $output = array();

    foreach (self::$doctrineArgs as $argName => $isString)
    {
      $arg = $props[$argName];
      unset($props[$argName]);
      if ($isString)
        $arg = sprintf("'%s'", $arg);
      $args[] = $arg;
    }

    $columnAlias = '';
    if ($props['columnName'])
    {
      $columnAlias = $props['columnName'] . ' as ';
    }
    unset($props['columnName']);

    $args[0] = sprintf("'%s%s'", $columnAlias, $args[0]);

    // what remains is considered to be constraints
    $args[] = $this->niceVarExport($props);

    $output[] = sprintf('$this->hasColumn(%s);', implode(', ', $args));

    return implode("\n", $output);
  }

  // exports this column in propel xml format
  public function addPropelXml(&$node)
  {
    $c = $node->addChild('column');

    $doc2proplDict = array_flip(self::$propel2docDictionary['types']);

    $c->addAttribute('name', $this->getName());

    // type
    $type = $this->properties['type'];
    if (array_key_exists($this->properties['type'], $doc2proplDict))
      $type = $doc2proplDict[$type];
    $c->addAttribute('type', $type);

    // size
    $size = $this->properties['size'];
    if ($type == 'varchar')
      $c->addAttribute('size', $size);

    // constraints
    $constraints = array_diff_key($this->properties, array_flip(array('name', 'type', 'size')));
    $doc2propelDict = array_flip(self::$propel2docDictionary['constraints']);
    foreach ($constraints as $constraint=>$value)
    {
      if (array_key_exists($constraint, $doc2propelDict))
        $constraint = $doc2propelDict[$constraint];
      $c->addAttribute($constraint, ($value ? 'true' : 'false'));
    }

    if ($rel = $this->getRelation())
    {
      $r = $node->addChild('foreign-key');
      $r->addAttribute('foreignTable', $rel['foreignTable']);
      $ref = $r->addChild('reference');
      $ref->addAttribute('local', $this->getName());
      $ref->addAttribute('foreign', $rel['foreignReference']);
    }
  }

  // exports this column in doctrine yml format
  public function asDoctrineYml()
  {
    $output = array();

    foreach($this->getProperties() as $key=>$value)
    {
      if ($key != 'name')
        $output[$key] = $value;
    }

    if ($relation = $this->getRelation())
    {
      $output = array_merge($output, $relation->asDoctrineYml());
    }

    return $output;
  }

  public function debug()
  {
    $debug = array();
    $debug['properties'] = $this->properties;
    $debug['relation'] = $this->relation;
    $debug['columnInfo'] = $this->getColumnInfo()->getAll();
    return $debug;
  }
}
