<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Internal;

use Doctrine\ORM\Internal\TopologicalSort;
use Doctrine\ORM\Internal\TopologicalSort\CycleDetectedException;
use Doctrine\Tests\OrmTestCase;

use function array_map;
use function array_search;
use function array_values;

class TopologicalSortTest extends OrmTestCase
{
    /** @var array<string, Node> */
    private $nodes = [];

    /** @var TopologicalSort */
    private $topologicalSort;

    protected function setUp(): void
    {
        $this->topologicalSort = new TopologicalSort();
    }

    public function testSimpleOrdering(): void
    {
        $this->addNodes('C', 'B', 'A', 'E');

        $this->addEdge('A', 'B');
        $this->addEdge('B', 'C');
        $this->addEdge('E', 'A');

        // There is only 1 valid ordering for this constellation
        self::assertSame(['C', 'B', 'A', 'E'], $this->computeResult());
    }

    public function testSkipOptionalEdgeToBreakCycle(): void
    {
        $this->addNodes('A', 'B');

        $this->addEdge('A', 'B', true);
        $this->addEdge('B', 'A', false);

        self::assertSame(['A', 'B'], $this->computeResult());
    }

    public function testBreakCycleByBacktracking(): void
    {
        $this->addNodes('A', 'B', 'C', 'D');

        $this->addEdge('A', 'B');
        $this->addEdge('B', 'C', true);
        $this->addEdge('C', 'D');
        $this->addEdge('D', 'A'); // closes the cycle

        // We can only break B -> C, so the result must be C -> D -> A -> B
        self::assertSame(['B', 'A', 'D', 'C'], $this->computeResult());
    }

    public function testCycleRemovedByEliminatingLastOptionalEdge(): void
    {
        // The cycle-breaking algorithm is currently very naive. It breaks the cycle
        // at the last optional edge while it backtracks. In this example, we might
        // get away with one extra update if we'd break A->B; instead, we break up
        // B->C and B->D.

        $this->addNodes('A', 'B', 'C', 'D');

        $this->addEdge('A', 'B', true);
        $this->addEdge('B', 'C', true);
        $this->addEdge('C', 'A');
        $this->addEdge('B', 'D', true);
        $this->addEdge('D', 'A');

        self::assertSame(['B', 'A', 'C', 'D'], $this->computeResult());
    }

    public function testGH7180Example(): void
    {
        // Example given in https://github.com/doctrine/orm/pull/7180#issuecomment-381341943

        $this->addNodes('E', 'F', 'D', 'G');

        $this->addEdge('D', 'G');
        $this->addEdge('D', 'F', true);
        $this->addEdge('F', 'E');
        $this->addEdge('E', 'D');

        self::assertSame(['G', 'D', 'E', 'F'], $this->computeResult());
    }

    public function testCommitOrderingFromGH7259Test(): void
    {
        // this test corresponds to the GH7259Test::testPersistFileBeforeVersion functional test
        $this->addNodes('A', 'B', 'C', 'D');

        $this->addEdge('D', 'A');
        $this->addEdge('A', 'B');
        $this->addEdge('D', 'C');
        $this->addEdge('A', 'D', true);

        // There is only multiple valid ordering for this constellation, but
        // the D -> A -> B ordering is important to break the cycle
        // on the nullable link.
        $correctOrders = [
            ['C', 'B', 'A', 'D'],
            ['B', 'C', 'A', 'D'],
            ['B', 'A', 'C', 'D'],
        ];

        self::assertContains($this->computeResult(), $correctOrders);
    }

    public function testCommitOrderingFromGH8349Case1Test(): void
    {
        $this->addNodes('A', 'B', 'C', 'D');

        $this->addEdge('D', 'A');
        $this->addEdge('A', 'B', true);
        $this->addEdge('B', 'D', true);
        $this->addEdge('B', 'C', true);
        $this->addEdge('C', 'D', true);

        // Many orderings are possible here, but the bottom line is A must be before D (it's the only hard requirement).
        $result = $this->computeResult();

        $indexA = array_search('A', $result, true);
        $indexD = array_search('D', $result, true);
        self::assertTrue($indexD > $indexA);
    }

    public function testCommitOrderingFromGH8349Case2Test(): void
    {
        $this->addNodes('A', 'B');

        $this->addEdge('B', 'A');
        $this->addEdge('B', 'A', true); // interesting: We have two edges in that direction
        $this->addEdge('A', 'B', true);

        // The B -> A requirement determines the result here
        self::assertSame(['A', 'B'], $this->computeResult());
    }

    public function testNodesMaintainOrderWhenNoDepencency(): void
    {
        $this->addNodes('A', 'B', 'C');

        // Nodes that are not constrained by dependencies shall maintain the order
        // in which they were added
        self::assertSame(['A', 'B', 'C'], $this->computeResult());
    }

    public function testNodesReturnedInDepthFirstOrder(): void
    {
        $this->addNodes('A', 'B', 'C');
        $this->addEdge('A', 'B');
        $this->addEdge('A', 'C');

        // We start on A and find that it has two dependencies on B and C,
        // added (as dependencies) in that order.
        // So, first we continue the DFS on B, because that edge was added first.
        // This gives the result order B, C, A.
        self::assertSame(['B', 'C', 'A'], $this->computeResult());
    }

    public function testNodesReturnedInDepthFirstOrderWithEdgesInDifferentOrderThanNodes(): void
    {
        $this->addNodes('A', 'B', 'C');
        $this->addEdge('A', 'C');
        $this->addEdge('A', 'B');

        // This is like testNodesReturnedInDepthFirstOrder, but it shows that for the two
        // nodes B and C that A depends upon, the result will follow the order in which
        // the edges were added.
        self::assertSame(['C', 'B', 'A'], $this->computeResult());
    }

    public function testNodesReturnedInDepthFirstOrderWithDependingNodeLast(): void
    {
        $this->addNodes('B', 'C', 'A');
        $this->addEdge('A', 'B');
        $this->addEdge('A', 'C');

        // This again is like testNodesReturnedInDepthFirstOrder, but this
        // time the node A that depends on B and C is added as the last node.
        // That means processing can go over B and C in the order they were given.
        // The order in which edges are added is not relevant (!), since at the time
        // the edges are evaluated, the nodes they point to have already been finished.
        self::assertSame(['B', 'C', 'A'], $this->computeResult());
    }

    public function testNodesReturnedInDepthFirstOrderWithDependingNodeLastAndEdgeOrderInversed(): void
    {
        $this->addNodes('B', 'C', 'A');
        $this->addEdge('A', 'C');
        $this->addEdge('A', 'B');

        // This again is like testNodesReturnedInDepthFirstOrderWithDependingNodeLast, but adds
        // the edges in the opposing order. Still, the result order is the same (!).
        // This may be surprising when comparing with testNodesReturnedInDepthFirstOrderWithEdgesInDifferentOrderThanNodes,
        // where the result order depends upon the _edge_ order.
        self::assertSame(['B', 'C', 'A'], $this->computeResult());
    }

    public function testDetectSmallCycle(): void
    {
        $this->addNodes('A', 'B');

        $this->addEdge('A', 'B');
        $this->addEdge('B', 'A');

        $this->expectException(CycleDetectedException::class);
        $this->computeResult();
    }

    public function testMultipleEdges(): void
    {
        // There may be more than one association between two given entities.
        // For the commit order, we only need to track this once, since the
        // result is the same (one entity must be processed before the other).
        //
        // In case one of the associations is optional and the other one is not,
        // we must honor the non-optional one, regardless of the order in which
        // they were declared.

        $this->addNodes('A', 'B');

        $this->addEdge('A', 'B', true); // optional comes first
        $this->addEdge('A', 'B', false);
        $this->addEdge('B', 'A', false);
        $this->addEdge('B', 'A', true); // optional comes last

        // Both edges A -> B and B -> A are non-optional, so this is a cycle
        // that cannot be broken.

        $this->expectException(CycleDetectedException::class);
        $this->computeResult();
    }

    public function testDetectLargerCycleNotIncludingStartNode(): void
    {
        $this->addNodes('A', 'B', 'C', 'D');

        $this->addEdge('A', 'B');
        $this->addEdge('B', 'C');
        $this->addEdge('C', 'D');
        $this->addEdge('D', 'B');

        // The sort has to start with the last node being added to make it possible that
        // the result is in the order the nodes were added (if permitted by edges).
        // That means the cycle will be detected when starting at D, so it is D -> B -> C -> D.

        try {
            $this->computeResult();
        } catch (CycleDetectedException $exception) {
            self::assertEquals(
                [$this->nodes['B'], $this->nodes['C'], $this->nodes['D'], $this->nodes['B']],
                $exception->getCycle()
            );
        }
    }

    private function addNodes(string ...$names): void
    {
        foreach ($names as $name) {
            $node               = new Node($name);
            $this->nodes[$name] = $node;
            $this->topologicalSort->addNode($node);
        }
    }

    private function addEdge(string $from, string $to, bool $optional = false): void
    {
        $this->topologicalSort->addEdge($this->nodes[$from], $this->nodes[$to], $optional);
    }

    /**
     * @return list<string>
     */
    private function computeResult(): array
    {
        return array_map(static function (Node $n): string {
            return $n->name;
        }, array_values($this->topologicalSort->sort()));
    }
}
