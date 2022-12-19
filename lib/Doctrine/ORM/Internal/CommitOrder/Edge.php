<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\CommitOrder;

/** @internal */
final class Edge
{
    /**
     * @var string
     * @readonly
     */
    public $from;

    /**
     * @var string
     * @readonly
     */
    public $to;

    /**
     * @var int
     * @readonly
     */
    public $weight;

    public function __construct(string $from, string $to, int $weight)
    {
        $this->from   = $from;
        $this->to     = $to;
        $this->weight = $weight;
    }
}
