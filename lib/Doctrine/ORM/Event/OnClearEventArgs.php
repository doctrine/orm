<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Provides event arguments for the onClear event.
 *
 * @link        www.doctrine-project.org
 */
class OnClearEventArgs extends EventArgs
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    /**
     * Retrieves associated EntityManager.
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }
}
