<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;

/** @extends LifecycleEventArgs<EntityManagerInterface> */
final class PostRemoveEventArgs extends LifecycleEventArgs
{
}
