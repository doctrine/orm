<?php
error_reporting(E_ALL);

$includePath = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'vendor';

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

    $tool = new DocTool('docs/en/root.txt');
    // $tool->setOption('clean-url', true);
    
    $supportedLangs = array('en', 'fi');
    foreach ($supportedLangs as $language) {
        include "lang/$language.php";
        $tool->addLanguage($lang[$language], $language);
    }
    
    $baseUrl = '';
    $title = 'Doctrine Manual';
    $section = null;
    
    if (isset($_GET['chapter'])) {
        $section = $tool->findByPath($_GET['chapter']);
        if ($tool->getOption('clean-url')) {
            $baseUrl = '../';
        }
    }
    
    if (isset($_GET['one-page'])) {
        $tool->setOption('one-page', true);
        $tool->setOption('max-level', 0);
        $section = null;
        $baseUrl = '';
    }
    
    if ($section) {
        while ($section->getLevel() > 1) {
            $section = $section->getParent();
        }
            
        $tool->setOption('section', $section);
        $title .= ' - Chapter ' . $section->getIndex() . ' ' . $section->getName();
    }
    
    if ($tool->getOption('clean-url')) {
        $tool->setOption('base-url', $baseUrl);
    }
    
    include 'template.php';
    
    $cache->end();
}
