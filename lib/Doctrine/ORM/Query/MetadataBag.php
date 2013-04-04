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

namespace Doctrine\ORM\Query;

/**
 * Bag for Query related Metadata such as Hints, First/Max Results Information.
 *
 * Data in this metadata bag affects the generation of SQL statements and as such
 * should be part of a Query Cache mechanism.
 *
 * @since 2.4
 */
class MetadataBag
{
    /**
     * Object Hydration
     */
    const HYDRATE_DEFAULT = 1;

    /**
     * The map of query hints.
     *
     * @var array
     */
    private $hints = array();

    /**
     * The maximum number of results to return (the "limit").
     *
     * @var integer
     */
    private $maxResults;

    /**
     * The first result to return (the "offset").
     *
     * @var integer
     */
    private $firstResult;

    /**
     * The hydration mode.
     *
     * @var integer
     */
    private $hydrationMode = self::HYDRATE_DEFAULT;

    /**
     * Return all query hints.
     *
     * @return array
     */
    public function getHints()
    {
        return $this->hints;
    }

    public function setFetchMode($class, $assocName, $fetchMode)
    {
        $this->hints['fetchMode'][$class][$assocName] = $fetchMode;
    }

    public function setHint($name, $value)
    {
        $this->hints[$name] = $value;
    }

    public function getHint($name)
    {
        return isset($this->hints[$name]) ? $this->hints[$name] : false;
    }

    public function setHydrationMode($hydrationMode)
    {
        $this->hydrationMode = $hydrationMode;
    }

    public function getHydrationMode()
    {
        return $this->hydrationMode;
    }

    /**
     * Sets the position of the first result to retrieve (the "offset").
     *
     * @param integer $firstResult The first result to return.
     *
     * @return void
     */
    public function setFirstResult($firstResult)
    {
        $this->firstResult = $firstResult;
    }

    /**
     * Gets the position of the first result the query object was set to retrieve (the "offset").
     * Returns NULL if {@link setFirstResult} was not applied to this query.
     *
     * @return integer The position of the first result.
     */
    public function getFirstResult()
    {
        return $this->firstResult;
    }

    /**
     * Sets the maximum number of results to retrieve (the "limit").
     *
     * @param integer $maxResults
     *
     * @return void
     */
    public function setMaxResults($maxResults)
    {
        $this->maxResults = $maxResults;
    }

    /**
     * Gets the maximum number of results the query object was set to retrieve (the "limit").
     * Returns NULL if {@link setMaxResults} was not applied to this query.
     *
     * @return integer Maximum number of results.
     */
    public function getMaxResults()
    {
        return $this->maxResults;
    }

    public function clearHints()
    {
        $this->hints = array();
    }

    /**
     * Generate a string representation of that can be used as a cache key.
     *
     * @return string
     */
    public function __toString()
    {
        ksort($this->hints);

        return md5(
            var_export($this->hints, true) .
            '&firstResult=' . $this->firstResult . '&maxResult=' . $this->maxResults .
            '&hydrationMode='.$this->hydrationMode.'DOCTRINE_QUERY_CACHE_SALT'
        );
    }
}
