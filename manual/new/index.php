<?php
error_reporting(E_ALL);

$includePath = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'vendor';

set_include_path($includePath);

require_once('Sensei/Sensei.php');
require_once('DocTool.php');

spl_autoload_register(array('Sensei', 'autoload'));

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
