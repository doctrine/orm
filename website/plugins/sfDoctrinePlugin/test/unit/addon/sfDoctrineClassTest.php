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
 * @author     Pavel Kunc
 * @author     Olivier Verdier <Olivier.Verdier@gmail.com>
 * @version    SVN: $Id: sfDoctrineClassTest.php 3455 2007-02-14 16:17:48Z chtito $
 */
//We need bootStrap
require_once(dirname(__FILE__).'/../../bootstrap/unit.php');

//TODO: add planned tests
$t = new lime_test(null,new lime_output_color());

$name = 'TestClass';
$class = new sfDoctrineClassSchema($name);

// ->__construct()
$t->diag('->__constuct()');
$t->is($class->getPhpName(), $name, '->__construct() takes first parameter as Class name');

// ->setPhpName()
$t->diag('->setPhpName()');
$newName = 'NewTestClass';
$class->setPhpName($newName);
$t->is($class->getPhpName(), $newName, '->setPhpName() sets new Class name');

// ->getColumns()
$t->diag('->getColumns()');
$t->is($class->getColumns(), array(),'->getColumns() returns array');

// ->isTable()
$t->diag('->isTable()');
$t->is($class->hasTable(), false, '->isTable() class is never table');

$t->diag('setting up a foreign relation');
$colName = 'colName';
$column = new sfDoctrineColumnSchema($colName, array('foreignClass'=>'otherClass'));
$class->addColumn($column);
$rel = $class->getRelation($colName);
$t->is($rel->get('localName'), $class->getPhpName().'s', 'default local name: plural of class name');
$t->is($rel->get('foreignName'), 'otherClass', 'default foreignName set to the foreign class name');

$t->diag('setting up options');
$class = new sfDoctrineClassSchema($name, array('options' => array('foo'=> 'bar')));
$classPhp = $class->asPhp();
$t->like($classPhp[0]['source'], '@\$this->option\(\'foo\', \'bar\'\)@', 'right output of the class options');

