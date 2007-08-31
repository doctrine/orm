<?php
/*
 * This file is part of the sfDoctrine package.
 * (c) 2006 Olivier Verdier <Olivier.Verdier@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @package    sfDoctrinePlugin
 * @subpackage sfDoctrineData
 * @author     Olivier Verdier <Olivier.Verdier@gmail.com>
 * @version    SVN: $Id: sfDoctrineData.class.php 4493 2007-06-30 00:47:02Z Jonathan.Wage $
 */
class sfDoctrineData extends sfData
{
  /**
   * connectionName 
   * 
   * @var mixed
   * @access protected
   */
  protected $connectionName = null;

  /**
   * loadData 
   * 
   * @param mixed $directory_or_file 
   * @param mixed $connectionName 
   * @access public
   * @return void
   */
  public function loadData($directory_or_file = null, $connectionName = null)
  {
    $this->connectionName = $connectionName;

    $fixture_files = $this->getFiles($directory_or_file);

    // wrap all database operations in a single transaction
    $con = sfDoctrine::connection($connectionName);
    try
    {
      $con->beginTransaction();

      $this->doLoadData($fixture_files);

      $con->commit();
    }
    catch (Exception $e)
    {
      $con->rollback();
      throw $e;
    }
  }

  /**
   * loadDataFromArray 
   * 
   * @param mixed $data 
   * @access public
   * @return void
   */
  public function loadDataFromArray($data)
  {
    $pendingRelations = array();

    if ($data === null)
    {
      // no data
      return;
    }

    // only for pake_echo_action
    require_once(sfConfig::get('sf_symfony_lib_dir').'/vendor/pake/pakeFunction.php');

    foreach ($data as $class => $entries)
    {
      pake_echo_action('Filling', sprintf('class "%s"', $class)."\t");
      // fetch a table object
      $table = sfDoctrine::getTable($class, $this->connectionName);

      $colNames = array_keys($table->getColumns());

      $tableName = $table->getTableName();

      // relation fields
      $relations = $table->getRelations();

      //echo "Class $class: ".implode(', ', array_keys($relations))."\n";

      if ($this->deleteCurrentData)
      {
        $q = new Doctrine_Query();
        $q->delete()->from($class);
        $q->execute();
      }

      // iterate through entries for this class
      // might have been empty just for force a table to be emptied on import
      if (is_array($entries))
      {
        foreach ($entries as $key => $columnAssignments)
        {
          // create a new entry in the database
          $obj = $table->create();
          $now = date("Y-m-d H:i:s", time());
          if($obj->getTable()->hasColumn('created_at')) 
          {
            $obj->set('created_at', $now);
          }
           
          if (!is_array($columnAssignments))
          {
            throw new Exception('You must give a name for each fixture data entry');
          }

          foreach ($columnAssignments as $name => $value)
          {
            $isRelation = isset($relations[$name]);
            // foreign key?
            if ($isRelation)
            {
              $rel = $relations[$name];
              // $relatedTable = $rel->getTable()->getTableName();
              $localKey = $rel->getLocal();
              $foreignKey = $rel->getForeign();

              $pendingRelations[] = array($obj, $localKey, $foreignKey, $value);
            }
            else              
            {
              // first check that the column exists
              if (!in_array($name, $colNames))
              {
                $error = 'Column "%s" does not exist for class "%s"';
                $error = sprintf($error, $name, $class);
                throw new sfException($error);         
              }

              $obj->rawSet($name, $value);
            }
          }
          
          $obj->save();
          
          // For progress meter
          echo '.';
          
          // save the id for future reference
          $pk = $obj->obtainIdentifier();
          if (isset($this->object_references[$key]))
          {
            throw new sfException(sprintf('The key "%s" is not unique', $key));
          }
          
          $this->object_references[$key] = $pk;
        }
      }
      echo "\n";
    }

    // now we take care of the pending relations
    foreach ($pendingRelations as $pending)
    {
      list($obj, $localKey, $foreignKey, $key) = $pending;
      
      if (!isset($this->object_references[$key]))
      {
        $error = 'No object with key "%s" is defined in your data file';
        $error = sprintf($error, $key);
        throw new sfException($error);
      }
      
      $foreignId = $this->object_references[$key][$foreignKey];
      
      $obj->rawSet($localKey, $foreignId);
      $obj->save();
    }
  }
  
  /**
   * loadMapBuilder 
   * 
   * @param mixed $class 
   * @access protected
   * @return void
   */
  protected function loadMapBuilder($class)
  {
    $class_map_builder = $class.'MapBuilder';
    if (!isset($this->maps[$class]))
    {
      if (!$classPath = sfCore::getClassPath($class_map_builder))
      {
        throw new sfException(sprintf('Unable to find path for class "%s".', $class_map_builder));
      }

      require_once($classPath);
      $this->maps[$class] = new $class_map_builder();
      $this->maps[$class]->doBuild();
    }
  }

  /**
   * dumpData 
   * 
   * @param mixed $directory_or_file 
   * @param string $tables 
   * @param string $connectionName 
   * @access public
   * @return void
   */
  public function dumpData($directory_or_file = null, $tables = 'all', $connectionName = 'propel')
  {
    $sameFile = true;
    if (is_dir($directory_or_file))
    {
      // multi files
      $sameFile = false;
    }
    else
    {
      // same file
      // delete file
    }
    
    $manager = Doctrine_Manager::getInstance();
    $con = $manager->getCurrentConnection();

    // get tables
    if ('all' === $tables || null === $tables)
    {
      $modelDirectories = array();
      $modelDirectories[] = sfConfig::get('sf_model_lib_dir').'/doctrine';
      
      $directories = sfFinder::type('dir')->maxdepth(0)->in(sfConfig::get('sf_model_lib_dir').'/doctrine');
     
      foreach($directories AS $directory)
      {
        if( strstr($directory, 'generated') )
        {
          continue;
        }
        
        $modelDirectories[] = $directory;
      }
      
      $tables = array();
      foreach($modelDirectories AS $directory)
      {
        $dirTables = sfFinder::type('file')->name('/(?<!Table)\.class.php$/')->maxdepth(0)->in($directory);
      
        foreach ($dirTables AS $key => $table)
        {
          $table = basename($table, '.class.php');
          $tables[] = $table;
        }
      }
    }
    else if (!is_array($tables))
    {
      $tables = array($tables);
    }

    $dumpData = array();
    
    foreach ($tables as $modelName)
    {
      $table = sfDoctrine::getTable($modelName, $this->connectionName);

      // get table name
      $tableName = $table->getTableName();
      
      $relations = $table->getRelations();

      // get columns
      $columns = $con->fetchAll('DESCRIBE '.$tableName);

      // get records
      //$records = $con->fetchAll('SELECT * FROM '.$tableName);
      $query = new Doctrine_Query();
      $query->from($modelName);
      $records = $query->execute();
      
      $dumpData[$modelName] = array();

      foreach($records AS $record)
      {
        $pk = $modelName;
        
        $values = array();
        foreach($columns AS $column)
        {
          $col = strtolower($column['Field']);
          
          try {
            $initialValue = $record[$col];
          } catch(Exception $e) {
            continue;
          }
          
          if( !$initialValue )
          {
            continue;
          }
          
          if ($column['Key'] == 'PRI')
          {
            $pk .= '_'.$initialValue;
          }
          else
          {
            $isForeignKey = false;
            foreach($relations AS $relation)
            {
              if( $relation->getLocal() == $col )
              {
                $isForeignKey = true;
                break;
              }
            }
            
            if( $isForeignKey )
            {
              $array = $relation->toArray();
              $values[$relation->getAlias()] = $array['class'].'_'.$initialValue;
            } else {
              $value = $initialValue;
              
              // Needed to maintain bool values
              if( is_bool($value) )
              {
                $value = $value ? 1:0;
              }
              
              $values[$col] = $value;
            }
          }
        }

        $dumpData[$modelName][$pk] = $values;
      }
    }

    // save to file(s)
    if ($sameFile)
    {
      $yaml = Spyc::YAMLDump($dumpData);
      file_put_contents($directory_or_file, $yaml);
    }
    else
    {
      foreach ($dumpData as $table => $data)
      {
        $yaml = Spyc::YAMLDump($data);
        file_put_contents($directory_or_file."/$table.yml", $yaml);
      }
    }
  }
}
