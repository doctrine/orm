<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal;

use InvalidArgumentException;

use function array_keys;
use function array_pop;
use function array_push;
use function min;
use function spl_object_id;

/**
 * StronglyConnectedComponents implements Tarjan's algorithm to find strongly connected
 * components (SCC) in a directed graph. This algorithm has a linear running time based on
 * nodes (V) and edges between the nodes (E), resulting in a computational complexity
 * of O(V + E).
 *
 * See https://en.wikipedia.org/wiki/Tarjan%27s_strongly_connected_components_algorithm
 * for an explanation and the meaning of the DFS and lowlink numbers.
 *
 * @internal
 */
final class StronglyConnectedComponents
{
    private const NOT_VISITED = 1;
    private const IN_PROGRESS = 2;
    private const VISITED     = 3;

    /**
     * Array of all nodes, indexed by object ids.
     *
     * @var array<int, object>
     */
    private $nodes = [];

    /**
     * DFS state for the different nodes, indexed by node object id and using one of
     * this class' constants as value.
     *
     * @var array<int, self::*>
     */
    private $states = [];

    /**
     * Edges between the nodes. The first-level key is the object id of the outgoing
     * node; the second array maps the destination node by object id as key.
     *
     * @var array<int, array<int, bool>>
     */
    private $edges = [];

    /**
     * DFS numbers, by object ID
     *
     * @var array<int, int>
     */
    private $dfs = [];

    /**
     * lowlink numbers, by object ID
     *
     * @var array<int, int>
     */
    private $lowlink = [];

    /** @var int */
    private $maxdfs = 0;

    /**
     * Nodes representing the SCC another node is in, indexed by lookup-node object ID
     *
     * @var array<int, object>
     */
    private $representingNodes = [];

    /**
     * Stack with OIDs of nodes visited in the current state of the DFS
     *
     * @var list<int>
     */
    private $stack = [];

    /** @param object $node */
    public function addNode($node): void
    {
        $id                = spl_object_id($node);
        $this->nodes[$id]  = $node;
        $this->states[$id] = self::NOT_VISITED;
        $this->edges[$id]  = [];
    }

    /** @param object $node */
    public function hasNode($node): bool
    {
        return isset($this->nodes[spl_object_id($node)]);
    }

    /**
     * Adds a new edge between two nodes to the graph
     *
     * @param object $from
     * @param object $to
     */
    public function addEdge($from, $to): void
    {
        $fromId = spl_object_id($from);
        $toId   = spl_object_id($to);

        $this->edges[$fromId][$toId] = true;
    }

    public function findStronglyConnectedComponents(): void
    {
        foreach (array_keys($this->nodes) as $oid) {
            if ($this->states[$oid] === self::NOT_VISITED) {
                $this->tarjan($oid);
            }
        }
    }

    private function tarjan(int $oid): void
    {
        $this->dfs[$oid]    = $this->lowlink[$oid] = $this->maxdfs++;
        $this->states[$oid] = self::IN_PROGRESS;
        array_push($this->stack, $oid);

        foreach ($this->edges[$oid] as $adjacentId => $ignored) {
            if ($this->states[$adjacentId] === self::NOT_VISITED) {
                $this->tarjan($adjacentId);
                $this->lowlink[$oid] = min($this->lowlink[$oid], $this->lowlink[$adjacentId]);
            } elseif ($this->states[$adjacentId] === self::IN_PROGRESS) {
                $this->lowlink[$oid] = min($this->lowlink[$oid], $this->dfs[$adjacentId]);
            }
        }

        $lowlink = $this->lowlink[$oid];
        if ($lowlink === $this->dfs[$oid]) {
            $representingNode = null;
            do {
                $unwindOid = array_pop($this->stack);

                if (! $representingNode) {
                    $representingNode = $this->nodes[$unwindOid];
                }

                $this->representingNodes[$unwindOid] = $representingNode;
                $this->states[$unwindOid]            = self::VISITED;
            } while ($unwindOid !== $oid);
        }
    }

    /**
     * @param object $node
     *
     * @return object
     */
    public function getNodeRepresentingStronglyConnectedComponent($node)
    {
        $oid = spl_object_id($node);

        if (! isset($this->representingNodes[$oid])) {
            throw new InvalidArgumentException('unknown node');
        }

        return $this->representingNodes[$oid];
    }
}
