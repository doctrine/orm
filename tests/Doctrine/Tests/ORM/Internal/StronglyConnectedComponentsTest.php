<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Internal;

use Doctrine\ORM\Internal\StronglyConnectedComponents;
use Doctrine\Tests\OrmTestCase;

class StronglyConnectedComponentsTest extends OrmTestCase
{
    /** @var array<string, Node> */
    private $nodes = [];

    /** @var StronglyConnectedComponents */
    private $stronglyConnectedComponents;

    protected function setUp(): void
    {
        $this->stronglyConnectedComponents = new StronglyConnectedComponents();
    }

    public function testFindStronglyConnectedComponents(): void
    {
        // A -> B <-> C -> D <-> E
        $this->addNodes('A', 'B', 'C', 'D', 'E');

        $this->addEdge('A', 'B');
        $this->addEdge('B', 'C');
        $this->addEdge('C', 'B');
        $this->addEdge('C', 'D');
        $this->addEdge('D', 'E');
        $this->addEdge('E', 'D');

        $this->stronglyConnectedComponents->findStronglyConnectedComponents();

        $this->assertNodesAreInSameComponent('B', 'C');
        $this->assertNodesAreInSameComponent('D', 'E');
        $this->assertNodesAreNotInSameComponent('A', 'B');
        $this->assertNodesAreNotInSameComponent('A', 'D');
    }

    public function testFindStronglyConnectedComponents2(): void
    {
        // A -> B -> C -> D -> B
        $this->addNodes('A', 'B', 'C', 'D');

        $this->addEdge('A', 'B');
        $this->addEdge('B', 'C');
        $this->addEdge('C', 'D');
        $this->addEdge('D', 'B');

        $this->stronglyConnectedComponents->findStronglyConnectedComponents();

        $this->assertNodesAreInSameComponent('B', 'C');
        $this->assertNodesAreInSameComponent('C', 'D');
        $this->assertNodesAreNotInSameComponent('A', 'B');
    }

    public function testFindStronglyConnectedComponents3(): void
    {
        //           v---------.
        // A -> B -> C -> D -> E
        //      ^--------´

        $this->addNodes('A', 'B', 'C', 'D', 'E');

        $this->addEdge('A', 'B');
        $this->addEdge('B', 'C');
        $this->addEdge('C', 'D');
        $this->addEdge('D', 'E');
        $this->addEdge('E', 'C');
        $this->addEdge('D', 'B');

        $this->stronglyConnectedComponents->findStronglyConnectedComponents();

        $this->assertNodesAreInSameComponent('B', 'C');
        $this->assertNodesAreInSameComponent('C', 'D');
        $this->assertNodesAreInSameComponent('D', 'E');
        $this->assertNodesAreInSameComponent('E', 'B');
        $this->assertNodesAreNotInSameComponent('A', 'B');
    }

    private function addNodes(string ...$names): void
    {
        foreach ($names as $name) {
            $node               = new Node($name);
            $this->nodes[$name] = $node;
            $this->stronglyConnectedComponents->addNode($node);
        }
    }

    private function addEdge(string $from, string $to, bool $optional = false): void
    {
        $this->stronglyConnectedComponents->addEdge($this->nodes[$from], $this->nodes[$to], $optional);
    }

    private function assertNodesAreInSameComponent(string $first, string $second): void
    {
        self::assertSame(
            $this->stronglyConnectedComponents->getNodeRepresentingStronglyConnectedComponent($this->nodes[$first]),
            $this->stronglyConnectedComponents->getNodeRepresentingStronglyConnectedComponent($this->nodes[$second])
        );
    }

    private function assertNodesAreNotInSameComponent(string $first, string $second): void
    {
        self::assertNotSame(
            $this->stronglyConnectedComponents->getNodeRepresentingStronglyConnectedComponent($this->nodes[$first]),
            $this->stronglyConnectedComponents->getNodeRepresentingStronglyConnectedComponent($this->nodes[$second])
        );
    }
}
