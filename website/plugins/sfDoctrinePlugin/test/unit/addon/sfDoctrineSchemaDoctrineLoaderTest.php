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
 * @version    SVN: $Id: sfDoctrineSchemaDoctrineLoaderTest.php 3455 2007-02-14 16:17:48Z chtito $
 */
//We need bootStrap
require_once(dirname(__FILE__).'/../../bootstrap/unit.php');

$t = new lime_test(null,new lime_output_color());

class sfDoctrineSchemaDoctrineLoaderTestProxy extends sfDoctrineSchemaDoctrineLoader {
  
  /**
   * Lime test object
   *
   * @var lime_test
   */
  private $t = null;
  
  /**
   * Constructor
   *
   * @param lime_test $testObject
   */
  function __construct($testObject) {
    $this->t = $testObject;
  }

  /**
   * Test launcher
   *
   * @param string $schema Path to schema file
   */
  function launchTests($schema) {
    $this->t->diag('->load()');
    $this->load($schema);
    $this->process();

    $yml = $this->asDoctrineYml();
    
    $this->t->diag('->getClasses()');
    $classes = $this->getClasses();
    $nbClasses = 12;
    $this->t->is(count($classes), $nbClasses,"->getClasses() should return $nbClasses classes from fixture.");
    
    $this->t->diag('->getClass()');
    $class = $this->getClass('TestClass');
    #$this->t->ok($class->isTable(),"->getClass() should return return class instance.");
    
    $this->t->diag('->parentTable()');
    $table = $this->parentTable($class);
    $this->t->is(get_class($table), 'sfDoctrineTableSchema', "->parentTable() should return table instance.");
    $this->t->is($this->getClass('ColAggregation')->getTableName(), 'parent_table', 'inheritance gets the right parent table');
    #$this->t->ok($this->getClass('SeparateTable')->isTable(), '"SeparateTable" is a table-class');

    $this->t->is($this->getClass('BookI18n')->getColumn('culture')->getProperty('type'), 'string', 'culture field is defined (as a string)');
    $rel = $this->getClass('BookI18n')->getRelation('id');
    $this->t->is($rel->get('localName'), 'BookI18n', 'i18n relation name is not a plural');

    $this->t->is($this->getClass('ColAggregation')->getTable()->getColumn('class_key')->getProperty('type'), 'integer', 'inheritance field is defined (as an integer)');

    $c = $this->getClass('SeparateTable');
    $SeparateTablePhp = $c->asPhp();
    $SeparateTableSource = $SeparateTablePhp[0]['source'];
    $this->t->like($SeparateTableSource, '/extends Parent/', 'The class "SeparateTable" extends Parent without having any class key field');

    $this->t->like($SeparateTableSource, '@setTableName\(\'separate_table\'\)@', 'class "SeparateTable" has both a table and inheritance');

    $this->t->like($SeparateTableSource, '@parent::setTableDefinition\(\);@', 'class "SeparateTable" calls parent::setTableDefinition');

    $colAggregationPhp = $this->getClass('ColAggregation')->asPhp();

    $this->t->like($colAggregationPhp[0]['source'], "@setInheritanceMap\(array\('class_key'=>1\)\)@", 'setInheritanceMap is properly set');

  $this->t->diag('relationships');
  $yangPhp = $this->getClass('Yin')->asPhp();
  $this->t->like($yangPhp[0]['source'], "#hasOne\('Yang as Yang', 'Yin.yang_id', 'id'\)#", 'one to one relationships is properly declared');

  $userPhp = $this->getClass('User')->asPhp();
  $this->t->like($userPhp[0]['source'], "#hasMany\('Book as Books', 'Book.author_id'\)#", 'hasMany is properly declared');

  $this->t->like($userPhp[0]['source'], "#hasMany\('Group as Groups', 'User2Group.group_id'\)#", 'has many to many properly declared');

  $userGroupPhp = $this->getClass('User2Group')->asPhp();
  $this->t->like($userGroupPhp[0]['source'], "#ownsOne\('User as User', 'User2Group.group_id', 'id'\)#", 'has many to many with cascade properly defined');
  }

}

//Load doctrine schema from fixtures and run tests
$schemaFixture = dirname(__FILE__)."/fixtures/doctrineTestSchema.yml";
$schema = new sfDoctrineSchemaDoctrineLoaderTestProxy($t);
$schema->launchTests($schemaFixture);