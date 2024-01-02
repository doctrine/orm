<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\PreUpdateEventArgs as BasePreUpdateEventArgs;

/**
 * Class that holds event arguments for a preUpdate event.
 *
 * @extends BasePreUpdateEventArgs<EntityManagerInterface>
 */
class PreUpdateEventArgs extends BasePreUpdateEventArgs
{
}
