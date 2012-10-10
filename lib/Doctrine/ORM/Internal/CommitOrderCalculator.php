<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Internal;

/**
 * The CommitOrderCalculator is used by the UnitOfWork to sort out the
 * correct order in which changes to entities need to be persisted.
 *
 * @since 	2.0
 * @author 	Roman Borschel <roman@code-factory.org>
 * @author	Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class CommitOrderCalculator
{
    const NOT_VISITED = 1;
    const IN_PROGRESS = 2;
    const VISITED = 3;

    private $nodeStates = array();
    private $classes = array(); // The nodes to sort
    private $relatedClasses = array();
    private $sorted = array();

    /**
     * Clears the current graph.
     *
     * @return void
     */
    public function clear()
    {
        $this->classes =
        $this->relatedClasses = array();
    }

    /**
     * Gets a valid commit order for all current nodes.
     *
     * Uses a depth-first search (DFS) to traverse the graph.
     * The desired topological sorting is the reverse postorder of these searches.
     *
     * @return array The list of ordered classes.
     */
    public function getCommitOrder()
    {
        // Check whether we need to do anything. 0 or 1 node is easy.
        $nodeCount = count($this->classes);

        if ($nodeCount <= 1) {
            return ($nodeCount == 1) ? array_values($this->classes) : array();
        }

        // Init
        foreach ($this->classes as $node) {
            $this->nodeStates[$node->name] = self::NOT_VISITED;
        }

        // Go
        foreach ($this->classes as $node) {
            if ($this->nodeStates[$node->name] == self::NOT_VISITED) {
                $this->visitNode($node);
            }
        }

        $sorted = array_reverse($this->sorted);

        $this->sorted = $this->nodeStates = array();

        return $sorted;
    }

    private function visitNode($node)
    {
        $this->nodeStates[$node->name] = self::IN_PROGRESS;

        if (isset($this->relatedClasses[$node->name])) {
            foreach ($this->relatedClasses[$node->name] as $relatedNode) {
                if ($this->nodeStates[$relatedNode->name] == self::NOT_VISITED) {
                    $this->visitNode($relatedNode);
                }
            }
        }

        $this->nodeStates[$node->name] = self::VISITED;
        $this->sorted[] = $node;
    }

    public function addDependency($fromClass, $toClass)
    {
        $this->relatedClasses[$fromClass->name][] = $toClass;
    }

    public function hasClass($className)
    {
        return isset($this->classes[$className]);
    }

    public function addClass($class)
    {
        $this->classes[$class->name] = $class;
    }
}
