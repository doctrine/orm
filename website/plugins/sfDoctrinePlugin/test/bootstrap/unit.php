<?php
/*
 * This file is part of the sfDoctrine package.
 * (c) 2006 Olivier Verdier <Olivier.Verdier@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @package    symfony.plugins
 * @subpackage sfDoctrine
 * @author     Pavel Kunc
 * @author     Olivier Verdier <Olivier.Verdier@gmail.com>
 * @version    SVN: $Id: unit.php 2690 2006-11-15 18:35:07Z chtito $
 */

$sfDoctrine_dir = realpath(dirname(__FILE__).'/../..');
define('SF_ROOT_DIR', realpath($sfDoctrine_dir.'/../..'));

// symfony directories
$project_config = SF_ROOT_DIR.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php';
if (file_exists($project_config)) // if we are in a project directory
  require $project_config;
else // the plugin is installed globally
  require 'config.php'; 



require_once $sf_symfony_lib_dir.'/../test/bootstrap/unit.php';

class sfDoctrineAutoLoader extends testAutoloader
{
  public static function sfDoctrineInitialize()
  {
    //FIXME: loading all the sfDoctrine directory is probably not needed
    $files = pakeFinder::type('file')->name('*.php')->ignore_version_control()->in(realpath(dirname(__FILE__).'/../..'));
    foreach ($files as $file)
    {
      preg_match_all('~^\s*(?:abstract\s+|final\s+)?(?:class|interface)\s+(\w+)~mi', file_get_contents($file), $classes);
      foreach ($classes[1] as $class)
      {
        self::$class_paths[$class] = $file;
      }
    }
  }
}

sfDoctrineAutoLoader::initialize();
sfDoctrineAutoLoader::sfDoctrineInitialize();
