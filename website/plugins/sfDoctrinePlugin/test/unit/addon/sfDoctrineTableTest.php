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
 * @version    SVN: $Id: sfDoctrineTableTest.php 3455 2007-02-14 16:17:48Z chtito $
 */
//We need bootStrap
require_once(dirname(__FILE__).'/../../bootstrap/unit.php');

//TODO: add planned tests
$t = new lime_test(null,new lime_output_color());

$tableName = 'test';
$package = 'package';
$table = new sfDoctrineTableSchema($tableName,$package);

// ->__construct()
$t->diag('->construct()');
$t->is($table->getName(), $tableName, '->__construct() takes first parameter as Table name');
$t->is($table->getPackage(), $package, '->__construct() takes second parameter as package name');

// ->setName()
$t->diag('->setName()');
$tableName = 'myTest';
$table->setName($tableName);
$t->is($table->getName(), $tableName, '->setName() sets new table name');

// ->addClass()
//TODO: need test

// ->addPropelXmlClasses()
//TODO: need test
