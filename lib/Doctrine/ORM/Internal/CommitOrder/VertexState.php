<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\CommitOrder;

/** @internal */
enum VertexState
{
    case NotVisited;
    case InProgress;
    case Visited;
}
