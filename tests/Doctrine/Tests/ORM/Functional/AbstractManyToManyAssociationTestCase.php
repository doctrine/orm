<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\Collection;
use Doctrine\Tests\OrmFunctionalTestCase;
use function count;
use function sprintf;

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
        return count($this->em->getConnection()->executeQuery(sprintf('
            SELECT %s
              FROM %s
             WHERE %s = ?
               AND %s = ?
        ', $this->firstField, $this->table, $this->firstField, $this->secondField), [$firstId, $secondId])->fetchAll());

        return count($this->em->getConnection()->executeQuery(sprintf(
            'SELECT %s FROM %s WHERE %s = ? AND %s = ?',
            $this->firstField,
            $this->table,
            $this->firstField,
            $this->secondField
        ), [$firstId, $secondId])->fetchAll());
    }

    public function assertCollectionEquals(Collection $first, Collection $second)
    {
        return $first->forAll(static function ($k, $e) use ($second) {
            return $second->contains($e);
        });
    }
}
