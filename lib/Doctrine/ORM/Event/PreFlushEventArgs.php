<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\ManagerEventArgs;

/**
 * Provides event arguments for the preFlush event.
 *
 * @extends ManagerEventArgs<EntityManagerInterface>
 */
class PreFlushEventArgs extends ManagerEventArgs
{
}
