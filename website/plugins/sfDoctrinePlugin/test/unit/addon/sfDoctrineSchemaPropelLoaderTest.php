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
 * @version    SVN: $Id: sfDoctrineSchemaPropelLoaderTest.php 3455 2007-02-14 16:17:48Z chtito $
 */
//We need bootStrap
require_once(dirname(__FILE__).'/../../bootstrap/unit.php');

$t = new lime_test(null, new lime_output_color());

class sfDoctrineSchemaPropelLoaderTestProxy extends sfDoctrineSchemaPropelLoader {
  
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
    
    $this->t->diag('->getTables()');
    $tables = $this->tables;
    $this->t->is(count($tables),2,"->getTables() should return 2 table from fixture.");
    $this->t->ok(in_array('testTable', array_keys($tables)), "->getTables() should return 'testTable' from fixture.");
    
    $this->t->diag('->classes');
    $this->t->is(count($this->classes),2,"->classes should have 2 class from fixture");
    $this->t->ok($this->getClass('TestTable'),"->classes should have 'TestTable' from fixture.");
    
    $this->t->ok($this->getClass('TestTable')->getColumn('dummy_id')->hasRelation(), 'foreign relation is properly imported');

    
    #$this->t->diag('->asDoctrineYml()');
    #$yml = $this->asDoctrineYml();
    #$this->t->cmp_ok(strlen($yml['source']), '>', 0, "->asDoctrineYml() doctrine YAML shoudl not be empty.");
    
    $this->t->diag('->findClassByTableName()');
    $this->t->is($this->findClassByTableName('testTable')->getPhpName(),'TestTable', "->findClassByTableName() returns 'TestTable' class for 'testTable' table.");
    
    $yml = $this->asDoctrineYml();
    $yml = $yml['source'];
    $this->t->like($yml, '@cascadeDelete: 1@', 'onDelete is generated');
  }
}

//Load Propel schema from fixtures and run tests
$schemaFixture = dirname(__FILE__)."/fixtures/propelTestSchema.xml";
$schema = new sfDoctrineSchemaPropelLoaderTestProxy($t);
$schema->launchTests($schemaFixture);

$schemaFixture = dirname(__FILE__)."/fixtures/propelTestSchema.yml";
$schema = new sfDoctrineSchemaPropelLoaderTestProxy($t);
$schema->launchTests($schemaFixture);