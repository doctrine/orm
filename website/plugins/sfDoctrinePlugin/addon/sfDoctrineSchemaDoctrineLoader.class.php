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
 * @version    SVN: $Id: sfDoctrineSchemaDoctrineLoader.class.php 3455 2007-02-14 16:17:48Z chtito $
 */

class sfDoctrineSchemaDoctrineLoader extends sfDoctrineDatabaseSchema
{
  // recursively finds out what a class table is
  // FIXME: check for infinite loop?
  protected function parentTable($class)
  {
    if ($class->hasTable())
      return $class->getTable();
      
    return $this->parentTable($this->getClass($class->getParentClassName()));
  }
  
  // associate a table to each class
  protected function fixTables()
  {
    foreach ($this->classes as $className => $class)
    {
      $table = $this->parentTable($class);
      $table->addClass($class);
    }
  }
  
  // set up the necessary fields in the i18n table: culture, id
  protected function addI18nFields()
  {
    foreach ($this->classes as $className => $class)
    {
      if (!$class->hasI18n())
        continue;
      $i18nClass = $this->getClass($class->getI18n('class')); 
      $cultureColumn = new sfDoctrineColumnSchema($class->getI18n('cultureField'), array('type'=> 'string', 'size'=> 100, 'primary'=> true));
      
      $i18nClass->addColumn($cultureColumn);
      
      // add the foreign key to the main table
      $idDesc = array('foreignClass'=>$className, 'localName'=>$i18nClass->getPhpName(), 'onDelete'=>'cascade', 'primary'=>true);
      $i18nClass->addColumn(new sfDoctrineColumnSchema('id', $idDesc));
    }
  }
  
  // adds the class key fields
  protected function addInheritanceFields()
  {
    foreach ($this->classes as $className => $class)
      if ($class->hasOneTableInheritance())
      {
        $inh = $class->getInheritance();
        $class->getTable()->addColumn(new sfDoctrineColumnSchema($inh['keyField'], array('type'=>'integer')));
      }
  }

  public function load($file, $package = null)
  {
    $schema = sfYaml::load($file);
    
    foreach ($schema as $className => $cd)
    {
      if (!isset($cd['tableName']) && !isset($cd['inheritance']))
        throw new sfDoctrineSchemaException(sprintf('Class "%s" must have either a table or a parent', $className));

      $class = new sfDoctrineClassSchema($className, $cd);

      // add a table if necessary
      if (isset($cd['tableName']))
      {
        // this top class is actually a table
        $table = new sfDoctrineTableSchema($cd['tableName'], $package);
        $table->addClass($class);
        $this->tables[$cd['tableName']] = $table;
      }

      $this->classes[$className] = $class;
    }
  }

  public function process()
  {
    $this->fixTables();
    
    $this->addI18nFields();

    $this->fixRelationships();
    
    $this->addInheritanceFields();
  }

}