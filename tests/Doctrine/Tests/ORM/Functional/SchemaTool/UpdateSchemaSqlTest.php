<?php

namespace Doctrine\Tests\ORM\Functional\SchemaTool;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Functional tests for the Class Table Inheritance mapping strategy.
 *
 * @author Barrie Treloar <baerrach@gmail.com>
 */
class UpdateSchemaSqlTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
  protected function setUp()
  {
    $setName = 'company';
    $this->useModelSet($setName);
    parent::setUp();
    $this->classnamesToClassmetaData = $this->getClassnamesToClassMetadata(static::$_modelSets[$setName]);
  }

  public function testEmpty() {
  }

  /**
   *
   */
  public function testUpdateSchemaSql()
  {
    $tool = new SchemaTool($this->_em);

    $sql = $tool->getUpdateSchemaSql(array_values($this->classnamesToClassmetaData));
#    var_dump($sql);
  }

}
