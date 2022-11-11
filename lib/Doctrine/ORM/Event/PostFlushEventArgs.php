<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\ManagerEventArgs;

/**
 * Provides event arguments for the postFlush event.
 *
 * @extends ManagerEventArgs<EntityManagerInterface>
 */
class PostFlushEventArgs extends ManagerEventArgs
{
}
