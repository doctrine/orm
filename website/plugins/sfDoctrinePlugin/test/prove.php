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
 * @version    SVN: $Id: prove.php 2874 2006-11-29 16:48:01Z chtito $
 */
$testsDir = realpath(dirname(__FILE__));

require_once($testsDir.'/bootstrap/unit.php');

$h = new lime_harness(new lime_output_color());

$h->base_dir = $testsDir;

// cache autoload files
testAutoloader::initialize(true);

// unit tests
$h->register_glob($h->base_dir.'/unit/*/*Test.php');

// functional tests
//$h->register_glob($h->base_dir.'/functional/*Test.php');
//$h->register_glob($h->base_dir.'/functional/*/*Test.php');

// other tests
//$h->register_glob($h->base_dir.'/other/*Test.php');

$h->run();

testAutoloader::removeCache();
