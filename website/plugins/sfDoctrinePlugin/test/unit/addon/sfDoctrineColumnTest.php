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
 * @version    SVN: $Id: sfDoctrineColumnTest.php 3438 2007-02-10 15:31:31Z chtito $
 */
//We need bootStrap
require_once(dirname(__FILE__).'/../../bootstrap/unit.php');

//TODO: add planned tests
$t = new lime_test(null, new lime_output_color());

$colName = 'TestColumn';
$column = new sfDoctrineColumnSchema($colName);

// ->__construct(), special case without variable parameters
$t->diag('->__constuct()');
$t->is($column->getName(), $colName, '->__construct() takes first parameter as Column name');
$t->isa_ok($column->getColumnInfo(), 'sfParameterHolder', '->__construct() sets column infor to sfParameterHolder');
//Construct sets default values, nothing passed
$props = $column->getProperties();
$t->is($props['type'],'string',"->__construct() default type is 'string'");

$t->is($props['name'],$colName,"->__construct() sets property name to column name");

$column = new sfDoctrineColumnSchema($colName, array('foreignClass'=>'other'));
$props = $column->getProperties();
$t->is($props['type'], 'integer', 'default foreign key type is integer');

$t->diag('constraints');
$column = new sfDoctrineColumnSchema($colName, array('enum'=>true, 'noconstraint'=>true));
$props = $column->getProperties();

$t->is($props['enum'], true, 'constraints are stored properly');
$t->ok(!isset($props['notaconstraint']), 'false constraints are not stored');

$t->diag('short syntax');
$type = 'string';
$size = 10;
$shortTypeSize = "$type($size)";
$column = new sfDoctrineColumnSchema($colName, array('type'=>$shortTypeSize));
$t->is($column->getProperty('size'), $size, 'short array syntax for size');
$t->is($column->getProperty('type'), $type, 'short array syntax for type');

$column = new sfDoctrineColumnSchema($colName, $shortTypeSize);
$t->is($column->getProperty('size'), $size, 'short string syntax for size');
$t->is($column->getProperty('type'), $type, 'short string syntax for type');
$column = new sfDoctrineColumnSchema($column, 'boolean');
$t->is($column->getProperty('type'), 'boolean', 'short string syntax without size');

$t->diag('PHP output');
$type = 'integer';
$size = 456;
$constraint = 'primary';
$colSetup = array('type'=>$type, 'size'=>$size, 'columnName' => 'test_column', $constraint=>true);
$column = new sfDoctrineColumnSchema($colName, $colSetup);

$t->is($column->asPhp(), "\$this->hasColumn('test_column as $colName', '$type', $size, array (  '$constraint' => true,));", 'php output');

$t->diag('Doctrine YML output');
$t->is_deeply($column->asDoctrineYml(), $colSetup, 'Doctrine array output');

$colEnum = new sfDoctrineColumnSchema($colName, array('type'=>array('a a', 'b')));
#$t->like($colEnum->asPhp(), "|this->setEnumValues\('TestColumn', .*0=>'a',.*1=>'b'.*\);|", 'enum types are declared');

