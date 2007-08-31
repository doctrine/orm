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
 * @version    SVN: $Id: sfDoctrineTableSchema.class.php 3455 2007-02-14 16:17:48Z chtito $
 */

class sfDoctrineTableSchema extends sfDoctrineClassSchema
{
  // the classes associated to that table
  protected $classes;

  // table name
  protected $name;
  
  // package of that table (usually either a plugin name, or a propel schema name)
  protected $package;
  
  public function getName()
  {
    return $this->name;
  }
  
  public function setName($name)
  {
    $this->name = $name;
  }
  
  public function __construct($tableName, $package)
  {
    $this->setName($tableName);
    $this->package = $package;
  }
  
  public function addClass($class)
  {
    // we add the class and try to avoid duplication
    $this->classes[$class->getPhpName()] = $class;
    $class->setTable($this);
  }

  public function getClasses()
  {
    return $this->classes;
  }
  
  // exports this table in propel xml format
  public function addPropelXmlClasses(&$node)
  {
    $t = $node->addChild('table');
    $t->addAttribute('name', $this->getName());
    $t->addAttribute('phpName', $this->getPhpName());
    foreach($this->classes as $class)
    {
      $class->addPropelXmlColumns($t);
    }
  }
  
  public function getPackage()
  {
    return $this->package;
  }
}
