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
 * @author     Nathanael D. Noblet <nathanael@gnat.ca>
 * @version    SVN: $Id: sfPakeDoctrine.php 4878 2007-08-17 17:45:54Z Jonathan.Wage $
 */

pake_desc('converts propel schema.*ml into doctrine schema');
pake_task('doctrine-import', 'project_exists');

pake_desc('exports doctrine schemas to sql');
pake_task('doctrine-build-sql', 'project_exists');

pake_desc('insert sql for doctrine schemas in to database');
pake_task('doctrine-insert-sql', 'project_exists');

pake_desc('build Doctrine classes');
pake_task('doctrine-build-model', 'project_exists');

pake_desc('Creates Doctrine CRUD Module');
pake_task('doctrine-generate-crud', 'app_exists');

pake_desc('initialize a new doctrine admin module');
pake_task('doctrine-init-admin', 'app_exists');

pake_desc('dump data to yaml fixtures file');
pake_task('doctrine-dump-data', 'project_exists');

pake_desc('load data from yaml fixtures file');
pake_task('doctrine-load-data', 'project_exists');

pake_desc('load doctrine nested set data from nested set fixtures file');
pake_task('doctrine-load-nested-set', 'project_exists');

pake_desc('doctrine build all - generate model and initialize database, drops current database if exists');
pake_task('doctrine-build-all', 'project_exists');

pake_desc('doctrine build all load - generate model, initialize database, and load data from fixtures. Drops current database if exists');
pake_task('doctrine-build-all-load', 'project_exists');

pake_desc('doctrine build schema - build schema from an existing database');
pake_task('doctrine-build-schema', 'project_exists');

pake_desc('doctrine drop all - drop all database tables');
pake_task('doctrine-drop-all-tables', 'project_exists');

pake_desc('doctrine build database - initialize database, drop current database if exists');
pake_task('doctrine-build-db', 'project_exists');

pake_desc('doctrine drop database - drops database');
pake_task('doctrine-drop-db', 'project_exists');

function run_doctrine_drop_all_tables($task, $args)
{
  if (!count($args))
  {
    throw new Exception('You must provide the app.');
  }

  $app = $args[0];
  
  $env = empty($args[1]) ? 'dev' : $args[1];
  
  _load_application_environment($app, $env);
  
  $sf_root_dir = sfConfig::get('sf_root_dir');
  
  $declared = get_declared_classes();
  
  $directory = sfConfig::get('sf_lib_dir').DIRECTORY_SEPARATOR.'model'.DIRECTORY_SEPARATOR.'doctrine'.DIRECTORY_SEPARATOR;
  if ($directory !== null)
  {
      foreach ((array) $directory as $dir)
      {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir),
                                                RecursiveIteratorIterator::LEAVES_ONLY);
                                              
        foreach ($it as $file)
        {
            $e = explode('.', $file->getFileName());
            if (end($e) === 'php' && strpos($file->getFileName(), '.inc') === false)
            {
                require_once $file->getPathName();
            }
        }
    }
    
    $declared = array_diff(get_declared_classes(), $declared);
  }

  $parent = new ReflectionClass('Doctrine_Record');
  
  $sql = array();
  $fks = array();

  // we iterate trhough the diff of previously declared classes 
  // and currently declared classes
  foreach ($declared as $name)
  {
      $class = new ReflectionClass($name);
      $conn  = Doctrine_Manager::getInstance()->getConnectionForComponent($name);

      // check if class is an instance of Doctrine_Record and not abstract
      // class must have method setTableDefinition (to avoid non-Record subclasses like symfony's sfDoctrineRecord)
      if ($class->isSubclassOf($parent) && ! $class->isAbstract() && method_exists($class->getName(), 'setTableDefinition'))
      {
          $record = new $name();
          $table  = $record->getTable();
          
          try {
            pake_echo_action('doctrine', "dropping table '".$table->getTableName()."'");
            
            $table->getConnection()->export->dropTable($table->getTableName());
          } catch(Exception $e) {
            continue;
          }
      }
  }
}

function run_doctrine_load_data($task, $args)
{
  if (!count($args))
  { 
    throw new Exception('You must provide the app.');
  }
 
  $app = $args[0];
 
  if (!is_dir(sfConfig::get('sf_app_dir').DIRECTORY_SEPARATOR.$app))
  { 
    throw new Exception('The app "'.$app.'" does not exist.');
  }
 
  if (count($args) > 1 && $args[count($args) - 1] == 'append')
  { 
    array_pop($args);
    $delete = false;
  }
  else
  { 
    $delete = true;
  }
 
  $env = empty($args[1]) ? 'dev' : $args[1];
 
  _load_application_environment($app, $env);
  
  if (count($args) == 1)
  {
    if (!$pluginDirs = glob(sfConfig::get('sf_root_dir').'/plugins/*/data'))
    {
      $pluginDirs = array();
    }
    $fixtures_dirs = pakeFinder::type('dir')->name('fixtures')->in(array_merge($pluginDirs, array(sfConfig::get('sf_data_dir'))));
  }
  else
  {
    $fixtures_dirs = array_slice($args, 1);
  }
 
  $data = new sfDoctrineData();
  $data->setDeleteCurrentData($delete);
 
  foreach ($fixtures_dirs as $fixtures_dir)
  {
    if (!is_readable($fixtures_dir))
    {
      continue;
    }
 
    pake_echo_action('doctrine', sprintf('load data from "%s"', $fixtures_dir));
 
    $data->loadData($fixtures_dir);
  }
}

function run_doctrine_import($task, $args)
{
  $type = 'xml';
  if (isset($args[0]))
    $type = $args[0];

  $schemas = _doctrine_load('propel', $type, false);
  
  $doctrineSchemasDir = sfConfig::get('sf_config_dir').DIRECTORY_SEPARATOR.'doctrine'.DIRECTORY_SEPARATOR;
  
  pake_mkdirs($doctrineSchemasDir);

  foreach($schemas as $schema)
  {
    $doctrineYml = $schema->asDoctrineYml();
    $classes = $schema->getClasses();
    $class = array_pop($classes);
    $package = $class->getTable()->getPackage();
    $filePath = $package.'.yml';
    
    pake_echo_action('writing', $filePath);
    
    file_put_contents($doctrineSchemasDir.$filePath, $doctrineYml['source']);
  }
}

function run_doctrine_export($task, $args)
{
  $schemas = _doctrine_load('doctrine', 'yml', false);
  
  $configDir = sfConfig::get('sf_config_dir').DIRECTORY_SEPARATOR;
  
  foreach($schemas as $schema)
  {
    $propelXml = $schema->asPropelXml();
    
    // we do some tidying before echoing the xml
    $source = preg_replace(array('#</database#', '#<(/?)table#', '#<column#', '#<(/?)foreign-key#', '#<reference#'), array("\n</database", "\n<\\1table", "\n  <column", "\n  <\\1foreign-key", "\n    <reference",), $propelXml['source']);
    
    $filePath = $propelXml['name'].'-schema.xml';
    
    pake_echo_action('writing', $filePath);
    
    file_put_contents($configDir.$filePath, $source);
  }
}

function run_doctrine_insert_sql($task, $args)
{
  if (!count($args))
  {
    throw new Exception('You must provide the app.');
  }

  $app = $args[0];
  
  $env = empty($args[1]) ? 'dev' : $args[1];
  
  _load_application_environment($app, $env);
  
  $sf_root_dir = sfConfig::get('sf_root_dir');
  
  $directories = sfFinder::type('dir')->maxdepth(0)->ignore_version_control()->in(sfConfig::get('sf_model_lib_dir').'/doctrine');
  
  Doctrine::exportSchema($directories);
  
  pake_echo_action('doctrine', 'sql was inserted successfully');
  
  return;
}

function run_doctrine_build_sql($task,$args)
{
    if(count($args) < 1)
    {
        throw new Exception('You must provide your app name.');
    }
    
    $sf_root_dir = sfConfig::get('sf_root_dir');
    define('SF_APP',         $args[0]);
    $connection = isset($args[1])?$args[1]:'all';
    
    simpleAutoloader::registerCallable(array('Doctrine','autoload'));

    sfConfig::set('sf_app_module_dir',$sf_root_dir.DIRECTORY_SEPARATOR.'apps'.DIRECTORY_SEPARATOR.SF_APP.DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR);

    $doctrineSchemaPathScheme = DIRECTORY_SEPARATOR.'model'.DIRECTORY_SEPARATOR.'doctrine'.DIRECTORY_SEPARATOR;
    $doctrineModelDir = sfConfig::get('sf_lib_dir').$doctrineSchemaPathScheme;
    $generatedDir = $doctrineModelDir.'generated'.DIRECTORY_SEPARATOR;
    
    $tmp_dir = $sf_root_dir.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.md5(uniqid(rand(), true));

    $db_connections = sfYaml::load($sf_root_dir.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'databases.yml');
    if(!isset($db_connections[$connection]))
    {
        throw new sfException('Unable to find connection: '.$connection);
    }
    
    $connection = current($db_connections[$connection]);
    $db = new sfDoctrineDatabase();
    $db->initialize($connection['param']);
    
    $directories = sfFinder::type('dir')->maxdepth(0)->ignore_version_control()->in(sfConfig::get('sf_model_lib_dir').'/doctrine');

    foreach ($directories AS $directory)
    {
        $basename = basename($directory);
        $name = $basename == 'generated' ? 'doctrine':'doctrine-'.$basename;
  
        pake_echo_action("Building SQL", $name);
  
        $sql = implode(";\n\n",Doctrine::exportSql($directory)).";\n";
        $sql = str_replace(array(" (",") ",","),array("(\n ",")\n",",\n"),$sql);

        if (!is_dir($sf_root_dir.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'sql'))
        {
            mkdir($sf_root_dir.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'sql');
        }

        $fd = fopen($sf_root_dir.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'sql'.DIRECTORY_SEPARATOR.$name.'.model.sql','w+');
        fwrite($fd,$sql);
        fclose($fd);
    }
    
    return; 
}


function run_doctrine_build_model($task, $args)
{
  $schemas = _doctrine_load('doctrine', 'yml', true);
  $doctrineSchemaPathScheme = DIRECTORY_SEPARATOR.'model'.DIRECTORY_SEPARATOR.'doctrine'.DIRECTORY_SEPARATOR;
  $doctrineModelDir = sfConfig::get('sf_lib_dir').$doctrineSchemaPathScheme;

  $generatedDir = $doctrineModelDir.'generated'.DIRECTORY_SEPARATOR;
  pake_mkdirs($generatedDir);

  foreach($schemas as $db_schema)
  {
    foreach ($db_schema->getClasses() as $class)
    {
      foreach ($class->asPHP() as $cd)
      {
        $path = $doctrineModelDir;

        $package = $class->getTable()->getPackage();
        if ($package)
        {
          if (isset($cd['plugin']))
          {
            $path = sfConfig::get('sf_plugins_dir').DIRECTORY_SEPARATOR.$package.DIRECTORY_SEPARATOR.'lib'.$doctrineSchemaPathScheme;
          }
          else
          {
            $path.= $package.DIRECTORY_SEPARATOR;
          }
        }
        
        if (isset($cd['overwrite']))
        {
          $path .= 'generated'.DIRECTORY_SEPARATOR;
        }
        
        pake_mkdirs($path);

        $filePath = $cd['className'].'.class.php';
          
        // we overwrite only the base classes
        if (isset($cd['overwrite']) || !file_exists($path.$filePath)) 
        {
          pake_echo_action('writing', $filePath);
          file_put_contents($path.$filePath, $cd['source']);
        }
      }
    }
  }
}

function run_doctrine_build_all($task, $args)
{
  run_doctrine_drop_db($task, $args);
  run_doctrine_build_db($task, $args);
  run_doctrine_build_model($task, $args);
  //run_doctrine_insert_sql($task, $args);
}

function run_doctrine_build_all_load($task, $args)
{
  run_doctrine_build_all($task, $args);
  run_doctrine_load_data($task, $args);
}

function run_doctrine_build_schema($task, $args)
{
  // This will build schema from an existing database
  throw new Exception('Not implemented.');
}

function run_doctrine_drop_db($task, $args)
{
  if (!count($args))
  {
    throw new Exception('You must provide the app.');
  }

  $app = $args[0];
  
  $env = empty($args[1]) ? 'dev' : $args[1];
  
  _load_application_environment($app, $env);
  
  $databases = sfYaml::load(sfConfig::get('sf_root_dir').DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'databases.yml');
  
  $connectionBlockKey = $env;
  
  if(!isset($databases[$connectionBlockKey]))
  {
  	$connectionBlockKey = 'all';
  }

  
 
  $connections = $databases[$connectionBlockKey];
  
  $manager = Doctrine_Manager::getInstance();
  
  foreach ($connections AS $name => $info)
  {
    $dsnInfo = $manager->parseDsn($info['param']['dsn']);
    $connection = $manager->getConnection($name);
    
    try {
      echo "Drop database '".$dsnInfo['database']."' are you sure Y/N ?";
      $confirmation = strtolower(trim(fgets(STDIN)));
      if ($confirmation!='y') {
        pake_echo_action("cancelled");
        exit(1);
      }

      pake_echo_action('doctrine', "dropping database '".$dsnInfo['database']."'");
      
      $connection->export->dropDatabase($dsnInfo['database']);
    } catch (Exception $e) {
      pake_echo_action('doctrine', "could not drop database '".$dsnInfo['database']."'");
    }
  }
}

function run_doctrine_build_db($task, $args)
{
  $connectionName = isset($args[0]) ? $args[0]:'all';
  
  simpleAutoloader::registerCallable(array('Doctrine','autoload'));
  
  $databases = sfYaml::load(sfConfig::get('sf_root_dir').DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'databases.yml');
  
  if(!isset($databases[$connectionName]))
  {
  	$connectionName = 'all';
  }
      
  $connections = $databases[$connectionName];
  
  foreach($connections AS $name => $connection)
  {
    $dsn = $connection['param']['dsn'];
    $info = Doctrine_Manager::getInstance()->parseDsn($dsn);
    
    $dsn = $info['scheme'].':host='.$info['host'];
    $user = $info['user'];
    $password = $info['pass'];
    
    $connection = Doctrine_Manager::getInstance()->openConnection(new PDO($dsn, $user, $password), $name.'2');
    
    pake_echo_action('doctrine', "creating database '".$info['database']."'");
  
    try {
      $connection->export->createDatabase($info['database']);
    } catch(Exception $e) {
      pake_echo_action('doctrine', "could not create database '".$info['database']."'");
    }
  }
}

// FIXME: has to be rewritten to avoid code duplication
function run_doctrine_generate_crud($task,$args)
{
  if (count($args) < 2)
  {
    throw new Exception('You must provide your module name.');
  }

  if (count($args) < 3)
  {
    throw new Exception('You must provide your model class name.');
  }

  $app         = $args[0];
  $module      = $args[1];
  $model_class = $args[2];
  $theme = isset($args[3]) ? $args[3] : 'crud';

  // function variables
  $doctrineModelDir = sfConfig::get('sf_lib_dir').DIRECTORY_SEPARATOR.'model'.DIRECTORY_SEPARATOR.'doctrine'.DIRECTORY_SEPARATOR;
  $sf_root_dir = sfConfig::get('sf_root_dir');
  $sf_symfony_lib_dir = sfConfig::get('sf_symfony_lib_dir');
  $pluginDir = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..');
  $doctrineLibDir =$pluginDir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'doctrine'.DIRECTORY_SEPARATOR.'Doctrine'.DIRECTORY_SEPARATOR;
  $tmp_dir = $sf_root_dir.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.md5(uniqid(rand(), true));

  sfConfig::set('sf_module_cache_dir', $tmp_dir);
  sfConfig::set('sf_app_dir', $tmp_dir);
  // add classes to autoload function
  pake_echo_action('PluginDir', $pluginDir);

  simpleAutoloader::registerCallable(array('Doctrine','autoload'));

  // generate module
  $generator_manager = new sfGeneratorManager();
  $generator_manager->initialize();
  $generator_manager->generate('sfDoctrineAdminGenerator', array('model_class' => $model_class, 'moduleName' => $module, 'theme' => $theme));
  $moduleDir = $sf_root_dir.'/'.sfConfig::get('sf_apps_dir_name').'/'.$app.'/'.sfConfig::get('sf_app_module_dir_name').'/'.$module;
  
  // copy our generated module
  $finder = pakeFinder::type('any');
  pake_mirror($finder, $tmp_dir.'/auto'.ucfirst($module), $moduleDir);

  // change module name
  pake_replace_tokens($moduleDir.'/actions/actions.class.php', getcwd(), '', '', array('auto'.ucfirst($module) => $module));

  try
  {
    $author_name = $task->get_property('author', 'symfony');
  }
  catch (pakeException $e)
  {
    $author_name = 'Your name here';
  }

  $constants = array(
    'PROJECT_NAME' => $task->get_property('name', 'symfony'),
    'APP_NAME'     => $app,
    'MODULE_NAME'  => $module,
    'MODEL_CLASS'  => $model_class,
    'AUTHOR_NAME'  => $author_name,
  );

  // customize php files
  $finder = pakeFinder::type('file')->name('*.php');
  pake_replace_tokens($finder, $moduleDir, '##', '##', $constants);

  // delete temp files
  $finder = pakeFinder::type('any');
  pake_remove($finder, $tmp_dir);

  // for some reason the above does not remove the tmp dir as it should.
  // delete temp dir
  @rmdir($tmp_dir);
  
  // delete cache/tmp
  @rmdir(sfConfig::get('sf_cache_dir').'tmp');
}

// FIXME: has to be rewritten to avoid code duplication
function run_doctrine_init_admin($task, $args)
{
  if (count($args) < 2)
  {
    throw new Exception('You must provide your module name.');
  }

  if (count($args) < 3)
  {
    throw new Exception('You must provide your model class name.');
  }
    
  $app         = $args[0];
  $module      = $args[1];
  $model_class = $args[2];
  $theme       = isset($args[3]) ? $args[3] : 'default';

  try
  {
    $author_name = $task->get_property('author', 'symfony');
  }
  catch (pakeException $e)
  {
    $author_name = 'Your name here';
  }

  $constants = array(
    'PROJECT_NAME' => $task->get_property('name', 'symfony'),
    'APP_NAME'     => $app,
    'MODULE_NAME'  => $module,
    'MODEL_CLASS'  => $model_class,
    'AUTHOR_NAME'  => $author_name,
    'THEME'        => $theme,
  );

  $moduleDir = sfConfig::get('sf_root_dir').DIRECTORY_SEPARATOR.sfConfig::get('sf_apps_dir_name').DIRECTORY_SEPARATOR.$app.DIRECTORY_SEPARATOR.sfConfig::get('sf_app_module_dir_name').DIRECTORY_SEPARATOR.$module;
  
  // create module structure
  $finder = pakeFinder::type('any')->ignore_version_control()->discard('.sf');
  $dirs = sfLoader::getGeneratorSkeletonDirs('sfDoctrineAdmin', $theme);
  foreach($dirs as $dir)
  {
    echo $dir;
    if(is_dir($dir))
    {
      pake_mirror($finder, $dir, $moduleDir);
      break;
    }
  }

  // customize php and yml files
  $finder = pakeFinder::type('file')->name('*.php', '*.yml');
  pake_replace_tokens($finder, $moduleDir, '##', '##', $constants);
}


/**
 * run_doctrine_load_nested_set 
 * 
 * @param mixed $task 
 * @param mixed $args 
 * @access public
 * @return void
 */
function run_doctrine_load_nested_set($task, $args)
{
  if (!count($args))
  {
    throw new Exception('You must provide the app.');
  }

  $app = $args[0];

  if (!is_dir(sfConfig::get('sf_app_dir').DIRECTORY_SEPARATOR.$app))
  {
    throw new Exception('The app "'.$app.'" does not exist.');
  }

  if (!isset($args[1]))
  {
    throw new Exception('You must provide a filename.');
  }

  $filename = $args[1];

  $env = empty($args[2]) ? 'dev' : $args[2];
  
  _load_application_environment($app, $env);

  $model = sfInflector::classify($args[1]);
  $ymlName = sfInflector::tableize($args[1]);
  
  $ymlPath = sfConfig::get('sf_data_dir').'/'.$ymlName.'.yml';
  
  pake_echo_action('doctrine', 'loading nested set data for '.$model);
  pake_echo_action('doctrine', 'loading '.$ymlPath);
  
  $nestedSetData = sfYaml::load($ymlPath);

  _doctrine_load_nested_set_data($model, $nestedSetData);
}

/**
 * run_doctrine_dump_data 
 * 
 * @param mixed $task 
 * @param mixed $args 
 * @access public
 * @return void
 */
function run_doctrine_dump_data($task, $args)
{
  if (!count($args))
  {
    throw new Exception('You must provide the app.');
  }

  $app = $args[0];

  if (!is_dir(sfConfig::get('sf_app_dir').DIRECTORY_SEPARATOR.$app))
  {
    throw new Exception('The app "'.$app.'" does not exist.');
  }

  if (!isset($args[1]))
  {
    throw new Exception('You must provide a filename.');
  }

  $filename = $args[1];

  $env = empty($args[2]) ? 'dev' : $args[2];
  
  _load_application_environment($app, $env);

  if (!sfToolkit::isPathAbsolute($filename))
  {
    $dir = sfConfig::get('sf_data_dir').DIRECTORY_SEPARATOR.'fixtures';
    pake_mkdirs($dir);
    $filename = $dir.DIRECTORY_SEPARATOR.$filename;
  }

  pake_echo_action('doctrine', sprintf('dumping data to "%s"', $filename));

  $data = new sfDoctrineData();
  $data->dumpData($filename);
}

/**
 * _doctrine_load_nested_set_data 
 * 
 * @param mixed $model 
 * @param mixed $nestedSetData 
 * @param mixed $parent 
 * @access protected
 * @return void
 */
function _doctrine_load_nested_set_data($model, $nestedSetData, $parent = null)
{
  $manager = Doctrine_Manager::getInstance();

  foreach($nestedSetData AS $name => $data)
  {
    $children = array();
    $setters  = array();
    
    if( array_key_exists('children', $data) )
    {
      $children = $data['children'];
      unset($data['children']);
    }

    if( array_key_exists('setters', $data) )
    {
      $setters = $data['setters'];
      unset($data['setters']);
    }

    $record = new $model();
    
    if( is_array($setters) AND !empty($setters) )
    {
      foreach($setters AS $key => $value)
      {
        $record->set($key, $value);
      }
    }
    
    if( !$parent )
    {
      $manager->getTable($model)->getTree()->createRoot($record);
    } else {
      $parent->getNode()->addChild($record);
    }

    pake_echo_action('doctrine', 'loading '.str_repeat(' ', $record->getNode()->getLevel()).$name);

    if( is_array($children) AND !empty($children) )
    {
      _doctrine_load_nested_set_data($model, $children, $record);
    }
  }
}

function _findPropelSchemas($type)
{
  $preGlob = '*schema';
  $root = 'config';

  $extension = '.'.$type;

  $schemas = pakeFinder::type('file')->name($preGlob.$extension)->in($root);

  $schemasToLoad = array();
  foreach ($schemas as $schema)
  {
    // we store the name of the file as "package"
    $schemasToLoad[$schema] = basename($schema, $extension);
  }

  return $schemasToLoad;
}

function _findDoctrineSchemas()
{
  $schemasToLoad = array();

  // first we try with a connection mapping config file
  $connectionMappingPath = 'config/schemas.yml';
  if (file_exists($connectionMappingPath))
  {
    $connectionMapping = sfYaml::load($connectionMappingPath);

    foreach ($connectionMapping as $connection => $schemas)
    {
      foreach ($schemas as $schema)
      {
        $components = explode('/', $schema);
        $name = array_pop($components);
        $schemaPath = 'config/doctrine/'.$name.'.yml';
        if (!empty($components))
        {
          $packageName = $components[0];
          $schemaPath = sfConfig::get('sf_plugins_dir').DIRECTORY_SEPARATOR.$packageName.DIRECTORY_SEPARATOR.$schemaPath;
        }
        else
          $packageName = null;

        if (file_exists($schemaPath))
        {
          $schemasToLoad[$schemaPath] = $packageName;
        }
      }
    }
  }
  else // otherwise we load all the schemas in the doctrine directories
  {
    $preGlob = '*';
    $root = 'config'.DIRECTORY_SEPARATOR.'doctrine';

    $schemas = pakeFinder::type('file')->name($preGlob.'.yml')->in($root);

    if(count($schemas))
        $schemas = array_combine($schemas, array_fill(0, count($schemas), null));

    // adding the plugin schemas
    $pluginSchemas = array();
    $pluginSchemas = pakeFinder::type('file')->name($preGlob.'.yml')->in(glob('plugins/*/'.$root));
    $schemasToLoad = array();
    foreach ($pluginSchemas as $pluginSchema)
    {
      // we get the plugin name from the path file; not very elegant...
      $pluginName = basename(substr(dirname($pluginSchema), 0, -strlen($root)));
      $schemasToLoad[$pluginSchema] = $pluginName;
    }

    $schemasToLoad = array_merge($schemas, $schemasToLoad);
  }
  return $schemasToLoad;
}

function _doctrine_load($mode, $type, $aggregate)
{
  $schemasToLoad = array();

  if ($mode == 'doctrine')
  {
    $schemasToLoad = _findDoctrineSchemas();
  }
  elseif ($mode == 'propel')
  {
    $schemasToLoad = _findPropelSchemas($type);
  }
    
  if (!count($schemasToLoad))
  {
    throw new Exception('No schemas were found');
  }

  $dbSchemas = array();
  
  // schema loader class
  $schemaClass = 'sfDoctrineSchema'.ucfirst($mode).'Loader';
  
  $db_schema = new $schemaClass();
  $db_schemas = array();

  foreach ($schemasToLoad as $schema => $package)
  {
    if (!$aggregate)
    {
      $db_schema = new $schemaClass();
    }
    $relativeSchema = substr($schema, strlen(sfConfig::get('sf_root_dir'))+1);

    pake_echo_action('loading', 'Class descriptions from "'.$schema.'"');
    $db_schema->load($schema, $package);
    if (!$aggregate)
    {
      $db_schema->process();
      $db_schemas[] = $db_schema;
    }
  }

  if ($aggregate)
  {
    $db_schema->process();
    $db_schemas = array($db_schema);
  }

  return $db_schemas;
}

function _load_application_environment($app, $env)
{
  // define constants
  define('SF_ROOT_DIR',    sfConfig::get('sf_root_dir'));
  define('SF_APP',         $app);
  define('SF_ENVIRONMENT', $env);
  define('SF_DEBUG',       true);

  // get configuration
  require_once SF_ROOT_DIR.DIRECTORY_SEPARATOR.'apps'.DIRECTORY_SEPARATOR.SF_APP.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php';
  
  sfContext::getInstance();
}
