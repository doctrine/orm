<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\Collection;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Base class for testing a many-to-many association mapping (without inheritance).
 */
class AbstractManyToManyAssociationTestCase extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected $_firstField;
    protected $_secondField;
    protected $_table;

    public function assertForeignKeysContain($firstId, $secondId)
    {
        $this->assertEquals(1, $this->_countForeignKeys($firstId, $secondId));
    }

    public function assertForeignKeysNotContain($firstId, $secondId)
    {
        $this->assertEquals(0, $this->_countForeignKeys($firstId, $secondId));
    }

    protected function _countForeignKeys($firstId, $secondId)
    {
        return count($this->_em->getConnection()->executeQuery("
            SELECT {$this->_firstField}
              FROM {$this->_table}
             WHERE {$this->_firstField} = ?
               AND {$this->_secondField} = ?
        ", array($firstId, $secondId))->fetchAll());
    }

    public function assertCollectionEquals(Collection $first, Collection $second)
    {
        return $first->forAll(function($k, $e) use($second) { return $second->contains($e); });
    }
}
