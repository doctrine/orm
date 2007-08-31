<?php

/**
 * manual actions.
 *
 * @package    doctrine_website
 * @subpackage manual
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 2692 2006-11-15 21:03:55Z fabien $
 */
class manualActions extends sfActions
{
  /**
   * Executes index action
   *
   */
  public function executeIndex()
  {
    error_reporting(E_ALL);
    
    $trunk = dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))));
    $vendorPath = $trunk.DIRECTORY_SEPARATOR.'vendor';
    $manualPath = $trunk.DIRECTORY_SEPARATOR.'manual';
    
    $includePath = $vendorPath.PATH_SEPARATOR.$manualPath.DIRECTORY_SEPARATOR.'new'.DIRECTORY_SEPARATOR.'lib';
    
    set_include_path($includePath);

    require_once('Sensei/Sensei.php');
    require_once('DocTool.php');
    require_once('Cache.php');

    spl_autoload_register(array('Sensei', 'autoload'));

    // Executes the 'svn info' command for the current directory and parses the last
    // changed revision.
    $revision = 0;
    exec('svn info .', $output);
    foreach ($output as $line) {
        if (preg_match('/^Last Changed Rev: ([0-9]+)$/', $line, $matches)) {
            $revision = $matches[1];
            break;
        }
    }

    $cacheDir = './cache/';
    $cacheRevFile = $cacheDir . 'revision.txt';
    $cacheRev = 0;

    $cache = new Cache($cacheDir, 'cache');

    // Checks the revision cache files were created from
    if (file_exists($cacheRevFile)) {
        $cacheRev = (int) file_get_contents($cacheRevFile);
    }

    // Empties the cache directory and saves the current revision to a file, if SVN
    // revision is greater than cache revision
    if ($revision > $cacheRev) {
         $cache->clear();
         @file_put_contents($cacheRevFile, $revision);
    }


    if ($cache->begin()) { 

        $this->tool = new DocTool($manualPath.'/new/docs/en.txt');
        // $this->tool->setOption('clean-url', true);

        $baseUrl = '';
        $title = 'Doctrine Manual';
        $section = null;

        if (isset($_GET['chapter'])) {
            $section = $this->tool->findByPath($_GET['chapter']);
            if ($this->tool->getOption('clean-url')) {
                $baseUrl = '../';
            }
        }

        if (isset($_GET['one-page'])) {
            $this->tool->setOption('one-page', true);
            $this->tool->setOption('max-level', 0);
            $section = null;
            $baseUrl = '';
        }

        if ($section) {
            while ($section->getLevel() > 1) {
                $section = $section->getParent();
            }

            $this->tool->setOption('section', $section);
            $title .= ' - Chapter ' . $section->getIndex() . ' ' . $section->getName();
        }

        if ($this->tool->getOption('clean-url')) {
            $this->tool->setOption('base-url', $baseUrl);
        }
        
        $cache->end();
    }
  }
}