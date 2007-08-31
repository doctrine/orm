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
 * @version    SVN: $Id: sfDoctrineSchemaPropelLoader.class.php 3455 2007-02-14 16:17:48Z chtito $
 */

class sfDoctrineSchemaPropelLoader extends sfDoctrineDatabaseSchema
{
  // get the attributes parsed by the sfPropelDatabaseSchema class
  protected function getAttribute($tag, $attribute)
  {
    return isset($tag['_attributes'][$attribute]) ? $tag['_attributes'][$attribute] : null;
  }
  
  public function load($file, $package = null)
  {
    // we figure out what kind of file we are given
    $type = array_pop(explode('.', $file));
    $type2method = array('yml'=>'loadYAML', 'xml'=>'loadXML');
    if (isset($type2method[$type]))
      $method = $type2method[$type];
    else
      throw new sfDoctrineSchemaException(sprintf('Unkwnown method for extension "%s"', $type));
    
    $propelDatabaseSchema = new sfPropelDatabaseSchema();
    $propelDatabaseSchema->$method($file);
    $data = $propelDatabaseSchema->asArray();
          
    foreach ($propelDatabaseSchema->getTables() as $tb_name => $tableDesc)
    {
      // special table class
      // propel has only such classes (no inheritance support)
      $table = new sfDoctrineTableSchema($tb_name, $package);
      $this->tables[$tb_name] = $table;

      if (!($className = $this->getAttribute($tableDesc, 'phpName')))
        $className = sfInflector::camelize($tb_name); // wild guess

      $class = new sfDoctrineClassSchema($className);
      $table->addClass($class);

      // columns
      foreach ($propelDatabaseSchema->getChildren($tableDesc) as $col_name => $columnDescription)
      {
        if (($col_name == 'id')) // id is automatically generated in doctrine
          continue;
      
        $docCol = new sfDoctrineColumnSchema($col_name, $columnDescription, true);
        $class->addColumn($docCol);
      }

      $this->classes[$class->getPhpName()] = $class;
    }
  }

  public function process()
  {
    $this->fixRelationships();
  }
}