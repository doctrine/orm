<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilter;

use Doctrine\Tests\OrmFunctionalTestCase;

use function sprintf;
use function str_replace;

abstract class AbstractTest extends OrmFunctionalTestCase
{
    protected function generateMessage(string $message): string
    {
        $log = $this->getLastLoggedQuery();

        return sprintf("%s\nSQL: %s", $message, str_replace(['?'], (array) $log['params'], $log['sql']));
    }

    /**
     * @param object ...$entities
     */
    protected function clearCachedData(...$entities): void
    {
        foreach ($entities as $entity) {
            $this->_em->refresh($entity);
        }
    }

    /**
     * @param object ...$entities
     */
    protected function persistFlushClear(...$entities): void
    {
        foreach ($entities as $entity) {
            $this->_em->persist($entity);
        }

        $this->_em->flush();
        $this->_em->clear();
    }
}
