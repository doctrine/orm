<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\Collection;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Base class for testing a many-to-many association mapping (without inheritance).
 */
class AbstractManyToManyAssociationTestCase extends OrmFunctionalTestCase
{
    protected $firstField;
    protected $secondField;
    protected $table;

    public function assertForeignKeysContain($firstId, $secondId)
    {
        self::assertEquals(1, $this->countForeignKeys($firstId, $secondId));
    }

    public function assertForeignKeysNotContain($firstId, $secondId)
    {
        self::assertEquals(0, $this->countForeignKeys($firstId, $secondId));
    }

    protected function countForeignKeys($firstId, $secondId)
    {
        return count($this->em->getConnection()->executeQuery("
            SELECT {$this->firstField}
              FROM {$this->table}
             WHERE {$this->firstField} = ?
               AND {$this->secondField} = ?
        ", [$firstId, $secondId]
        )->fetchAll());
    }

    public function assertCollectionEquals(Collection $first, Collection $second)
    {
        return $first->forAll(function($k, $e) use($second) { return $second->contains($e); });
    }
}
