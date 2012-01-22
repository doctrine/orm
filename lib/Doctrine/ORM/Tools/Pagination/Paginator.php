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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Tools\Pagination;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Tools\Pagination\WhereInWalker;
use Doctrine\ORM\Tools\Pagination\CountWalker;
use Countable;
use IteratorAggregate;
use ArrayIterator;

/**
 * Paginator
 *
 * The paginator can handle various complex scenarios with DQL.
 *
 * @author Pablo Díez <pablodip@gmail.com>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @license New BSD
 */
class Paginator implements \Countable, \IteratorAggregate
{
    /**
     * @var Query
     */
    private $query;

    /**
     * @var bool
     */
    private $fetchJoinCollection;

    /**
     * @var int
     */
    private $count;

    /**
     * Constructor.
     *
     * @param Query|QueryBuilder $query A Doctrine ORM query or query builder.
     * @param Boolean $fetchJoinCollection Whether the query joins a collection (true by default).
     */
    public function __construct($query, $fetchJoinCollection = true)
    {
        if ($query instanceof QueryBuilder) {
            $query = $query->getQuery();
        }

        $this->query = $query;
        $this->fetchJoinCollection = (Boolean) $fetchJoinCollection;
    }

    /**
     * Returns the query
     *
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Returns whether the query joins a collection.
     *
     * @return Boolean Whether the query joins a collection.
     */
    public function getFetchJoinCollection()
    {
        return $this->fetchJoinCollection;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        if ($this->count === null) {
            /* @var $countQuery Query */
            $countQuery = $this->cloneQuery($this->query);

            if ( ! $countQuery->getHint(CountWalker::HINT_DISTINCT)) {
                $countQuery->setHint(CountWalker::HINT_DISTINCT, true);
            }

            $countQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, array('Doctrine\ORM\Tools\Pagination\CountWalker'));
            $countQuery->setFirstResult(null)->setMaxResults(null);

            try {
                $data =  $countQuery->getScalarResult();
                $data = array_map('current', $data);
                $this->count = array_sum($data);
            } catch(NoResultException $e) {
                $this->count = 0;
            }
        }
        return $this->count;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $offset = $this->query->getFirstResult();
        $length = $this->query->getMaxResults();

        if ($this->fetchJoinCollection) {
            $subQuery = $this->cloneQuery($this->query);
            $subQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, array('Doctrine\ORM\Tools\Pagination\LimitSubqueryWalker'))
                ->setFirstResult($offset)
                ->setMaxResults($length);

            $ids = array_map('current', $subQuery->getScalarResult());

            $whereInQuery = $this->cloneQuery($this->query);
            // don't do this for an empty id array
            if (count($ids) > 0) {
                $namespace = WhereInWalker::PAGINATOR_ID_ALIAS;

                $whereInQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, array('Doctrine\ORM\Tools\Pagination\WhereInWalker'));
                $whereInQuery->setHint(WhereInWalker::HINT_PAGINATOR_ID_COUNT, count($ids));
                $whereInQuery->setFirstResult(null)->setMaxResults(null);
                foreach ($ids as $i => $id) {
                    $i++;
                    $whereInQuery->setParameter("{$namespace}_{$i}", $id);
                }
            }

            $result = $whereInQuery->getResult($this->query->getHydrationMode());
        } else {
            $result = $this->cloneQuery($this->query)
                ->setMaxResults($length)
                ->setFirstResult($offset)
                ->getResult($this->query->getHydrationMode())
            ;
        }
        return new \ArrayIterator($result);
    }

    /**
     * Clones a query.
     *
     * @param Query $query The query.
     *
     * @return Query The cloned query.
     */
    private function cloneQuery(Query $query)
    {
        /* @var $cloneQuery Query */
        $cloneQuery = clone $query;
        $cloneQuery->setParameters($query->getParameters());
        foreach ($query->getHints() as $name => $value) {
            $cloneQuery->setHint($name, $value);
        }

        return $cloneQuery;
    }
}

