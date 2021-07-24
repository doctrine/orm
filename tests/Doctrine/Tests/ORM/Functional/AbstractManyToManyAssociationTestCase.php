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
    /** @var string */
    protected $firstField;

    /** @var string */
    protected $secondField;

    /** @var string */
    protected $table;

    public function assertForeignKeysContain($firstId, $secondId): void
    {
        self::assertEquals(1, $this->countForeignKeys($firstId, $secondId));
    }

    public function assertForeignKeysNotContain($firstId, $secondId): void
    {
        self::assertEquals(0, $this->countForeignKeys($firstId, $secondId));
    }

    protected function countForeignKeys($firstId, $secondId): int
    {
        return count($this->_em->getConnection()->executeQuery(sprintf(
            <<<'SQL'
            SELECT %s
              FROM %s
             WHERE %s = ?
               AND %s = ?
SQL
            ,
            $this->firstField,
            $this->table,
            $this->firstField,
            $this->secondField
        ), [$firstId, $secondId])->fetchAll());
    }

    public function assertCollectionEquals(Collection $first, Collection $second): bool
    {
        return $first->forAll(static function ($k, $e) use ($second): bool {
            return $second->contains($e);
        });
    }
}
