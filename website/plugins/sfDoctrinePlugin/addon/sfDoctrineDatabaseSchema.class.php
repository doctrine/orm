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
 * @version    SVN: $Id: sfDoctrineDatabaseSchema.class.php 3455 2007-02-14 16:17:48Z chtito $
 */

/* 
 - class: contains a bunch of columns, toMany relationships, inheritance
   information, i18n information
 - table: a special class that is actually a table
 - column: contains the doctrine properties (name, type, size) and the toOne relation information

 */

class sfDoctrineDatabaseSchema
{
  // the class descriptions
  protected $classes = array();

  // a subset of the array above: classes which are also tables
  protected $tables = array();

  public function getClasses()
  {
    return $this->classes;
  }

  protected function getClass($className)
  {
    if (isset($this->classes[$className]))
      return $this->classes[$className];
    throw new sfDoctrineSchemaException(sprintf('The class "%s" has no description', $className));
  }

  // retrieves a class object from its table name
  protected function findClassByTableName($tableName)
  {
    foreach ($this->tables as $table)
      if ($table->getName() == $tableName)
      {
        $tableClasses = $table->getClasses();
        if (count($tableClasses) != 1)
          throw new sfDoctrineSchemaException(sprintf('No unique class is associated to table "%s"', $tableName));
        return array_pop($tableClasses);
      }
    throw new sfDoctrineSchemaException(sprintf('Table "%s" not found', $tableName));
  }

  // set the one to many and many to many relationships
  // finds out what are the foreign classes or foreign tables
  protected function fixRelationships()
  {
    foreach ($this->classes as $className => $class)
    {
      foreach ($class->getColumns() as $relCol)
        if ($relation = $relCol->getRelation())
        {
          // if no foreignClass was specified (import from propel) we find it out
          if (!$relation->get('foreignClass'))
          {
            $foreignClass = $this->findClassByTableName($relation->get('foreignTable'));
            $relation->set('foreignClass', $foreignClass->getPhpName());
          }

          // if foreignTable was not set (only used for export to propel)
          // we figure it out
          if (!$relation->get('foreignTable'))
          {
            $className = $relation->get('foreignClass');
            $relation->set('foreignTable', $this->getClass($className)->getTableName());
          }

          // the relation is a many2many
          if ($relation->get('counterpart'))
          {
            $counterpartRel = $class->getRelation($relation->get('counterpart'));
            $relation->set('otherClass', $counterpartRel->get('foreignClass'));
          }

          // we copy all the toOne relations to the corresponding
          // foreign class
          $rel = $relCol->getRelation();
          $this->getClass($rel->get('foreignClass'))->addToMany($rel); // FIXME: don't copy here

        }
    }
  }


  // exports the current schema as a propel xml file
  public function asPropelXml()
  {
    $xml = new SimpleXmlElement(sprintf('<?xml version="1.0" encoding="UTF-8" ?>
<database name="%s" defaultIdMethod="native"></database>', 'connection'));

    foreach ($this->tables as $table)
    {
      $table->addPropelXmlClasses($xml);
    }

    return array('source'=>$xml->asXml());
  }

  // exports the current schema in a sfDoctrine yml file
  public function asDoctrineYml()
  {
    $ymlClasses = array();

    foreach ($this->classes as $class)
    {
      $ymlClasses[$class->getPhpName()] = $class->asDoctrineYml();
    }
    return array('source'=>sfYaml::dump($ymlClasses));
  }

  public function debug()
  {
    $debug = array();
    foreach ($this->classes as $class)
    {
      $debug[$class->getPhpName()] = $class->debug();
    }
    return $debug;
  }

}





class sfDoctrineSchemaException extends sfException
{
  public function __construct($message = null, $code = 0)
  {
    $this->setName('sfDoctrineSchemaException');
    parent::__construct($message, $code);
  }
}