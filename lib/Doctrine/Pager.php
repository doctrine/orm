<?php

/*
 *  $Id$
 *
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
 * <http://www.phpdoctrine.com>.
 */

/**
 * Doctrine_Pager
 *
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @package     Doctrine
 * @subpackage  Pager
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @link        www.phpdoctrine.org
 * @since       0.9
 */
class Doctrine_Pager
{
    /**
     * @var Doctrine_Query $_query      Doctrine_Query object related to the pager
     */
    protected $_query;

    /**
     * @var Doctrine_Query $_countQuery Doctrine_Query object related to the counter of pager
     */
    protected $_countQuery;

    /**
     * @var array $_countQueryParams    Hold the params to be used by Doctrine_Query counter object of pager
     */
    protected $_countQueryParams;

    /**
     * @var integer $_numResults        Number of results found
     */
    protected $_numResults;

    /**
     * @var integer $_maxPerPage        Maximum number of itens per page
     */
    protected $_maxPerPage;

    /**
     * @var integer $page               Current page
     */
    protected $_page;

    /**
     * @var integer $_lastPage          Last page (total of pages)
     */
    protected $_lastPage;

    /**
     * @var boolean $_executed          Pager was initialized (called "execute" at least once)
     */
    protected $_executed;



    /**
     * __construct
     *
     * @param mixed $query     Accepts either a Doctrine_Query object or a string 
     *                        (which does the Doctrine_Query class creation).
     * @param int $page     Current page
     * @param int $maxPerPage     Maximum itens per page
     * @return void
     */
    public function __construct($query, $page, $maxPerPage = 0)
    {
        $this->_setExecuted(false);

        $this->_setQuery($query);
        $this->_setPage($page);

        $this->setMaxPerPage($maxPerPage);
    }


    /**
     * _initialize
     *
     * Initialize Pager object calculating number of results
     *
     * @param $params  Optional parameters to Doctrine_Query::execute
     * @return void
     */
    protected function _initialize($params = array())
    {
        // retrieve the number of items found
        $count = $this->getCountQuery()->count($this->getCountQueryParams($params));
        $this->_setNumResults($count);

        $this->_adjustOffset();

	$this->_setExecuted(true);
    }


    /**
     * _adjustOffset
     *
     * Adjusts last page of Doctrine_Pager, offset and limit of Doctrine_Query associated
     *
     * @return void
     */
    protected function _adjustOffset()
    {
        if (!$this->getExecuted()) {
            // Define new total of pages
            $this->_setLastPage(
                max(1, ceil($this->getNumResults() / $this->getMaxPerPage()))
            );
            $offset = ($this->getPage() - 1) * $this->getMaxPerPage();

            // Assign new offset and limit to Doctrine_Query object
            $p = $this->getQuery();
            $p->offset($offset);
            $p->limit($this->getMaxPerPage());
        }
    }


    /**
     * getExecuted
     *
     * Returns the check if Pager was already executed at least once
     *
     * @return boolen        Pager was executed
     */
    public function getExecuted()
    {
        return $this->_executed;
    }


    /**
     * _setExecuted
     *
     * Defines if Pager was already executed
     *
     * @param $executed       Pager was executed
     * @return void
     */
    protected function _setExecuted($executed)
    {
        $this->_executed = $executed;
    }


    /**
     * getNumResults
     *
     * Returns the number of results found
     *
     * @return int        the number of results found
     */
    public function getNumResults()
    {
        if ($this->getExecuted()) {
            return $this->_numResults;
        }

        throw new Doctrine_Pager_Exception(
            'Cannot retrieve the number of results of a not yet executed Pager query'
        );
    }


    /**
     * _setNumResults
     *
     * Defines the number of total results on initial query
     *
     * @param $nb       Number of results found on initial query fetch
     * @return void
     */
    protected function _setNumResults($nb)
    {
        $this->_numResults = $nb;
    }


    /**
     * getFirstPage
     *
     * Returns the first page
     *
     * @return int        first page
     */
    public function getFirstPage()
    {
        return 1;
    }


    /**
     * getLastPage
     *
     * Returns the last page (total of pages)
     *
     * @return int        last page (total of pages)
     */
    public function getLastPage()
    {
        if ($this->getExecuted()) {
            return $this->_lastPage;
        }

        throw new Doctrine_Pager_Exception(
            'Cannot retrieve the last page number of a not yet executed Pager query'
        );
    }


    /**
     * _setLastPage
     *
     * Defines the last page (total of pages)
     *
     * @param $page       last page (total of pages)
     * @return void
     */
    protected function _setLastPage($page)
    {
        $this->_lastPage = $page;

        if ($this->getPage() > $page) {
            $this->_setPage($page);
        }
    }


    /**
     * getLastPage
     *
     * Returns the current page
     *
     * @return int        current page
     */
    public function getPage()
    {
        return $this->_page;
    }


    /**
     * getLastPage
     *
     * Returns the next page
     *
     * @return int        next page
     */
    public function getNextPage()
    {
        if ($this->getExecuted()) {
            return $this->_lastPage;
        }

        throw new Doctrine_Pager_Exception(
            'Cannot retrieve the last page number of a not yet executed Pager query'
        );return min($this->getPage() + 1, $this->getLastPage());
    }


    /**
     * getLastPage
     *
     * Returns the previous page
     *
     * @return int        previous page
     */
    public function getPreviousPage()
    {
        if ($this->getExecuted()) {
            return max($this->getPage() - 1, $this->getFirstPage());
        }

        throw new Doctrine_Pager_Exception(
            'Cannot retrieve the previous page number of a not yet executed Pager query'
        );
    }


    /**
     * haveToPaginate
     *
     * Return true if it's necessary to paginate or false if not
     *
     * @return bool        true if it is necessary to paginate, false otherwise
     */
    public function haveToPaginate()
    {
        if ($this->getExecuted()) {
            return $this->getNumResults() > $this->getMaxPerPage();
        }

        throw new Doctrine_Pager_Exception(
            'Cannot know if it is necessary to paginate a not yet executed Pager query'
        );
    }


    /**
     * setPage
     *
     * Defines the current page and automatically adjust offset and limits
     *
     * @param $page       current page
     * @return void
     */
    public function setPage($page)
    {
        $this->_setPage($page);
        $this->_setExecuted(false);
    }


    /**
     * _setPage
     *
     * Defines the current page
     *
     * @param $page       current page
     * @return void
     */
    private function _setPage($page)
    {
        $page = intval($page);
        $this->_page = ($page <= 0) ? 1 : $page;
    }


    /**
     * getLastPage
     *
     * Returns the maximum number of itens per page
     *
     * @return int        maximum number of itens per page
     */
    public function getMaxPerPage()
    {
        return $this->_maxPerPage;
    }


    /**
     * setMaxPerPage
     *
     * Defines the maximum number of itens per page and automatically adjust offset and limits
     *
     * @param $max       maximum number of itens per page
     * @return void
     */
    public function setMaxPerPage($max)
    {
        if ($max > 0) {
            $this->_maxPerPage = $max;
        } else if ($max == 0) {
            $this->_maxPerPage = 25;
        } else {
            $this->_maxPerPage = abs($max);
        }

        $this->_setExecuted(false);
    }


    /**
     * getQuery
     *
     * Returns the Doctrine_Query collector object related to the pager
     *
     * @return Doctrine_Query    Doctrine_Query object related to the pager
     */
    public function getQuery()
    {
        return $this->_query;
    }


    /**
     * _setQuery
     *
     * Defines the collector query to be used by pager
     *
     * @param Doctrine_Query     Accepts either a Doctrine_Query object or a string 
     *                           (which does the Doctrine_Query class creation).
     * @return void
     */
    protected function _setQuery($query)
    {
        if (is_string($query)) {
            $query = Doctrine_Query::create()->parseQuery($query);
        }

        $this->_query = $query;
    }


    /**
     * getCountQuery
     *
     * Returns the Doctrine_Query object that is used to make the count results to pager
     *
     * @return Doctrine_Query     Doctrine_Query object related to the pager
     */
    public function getCountQuery()
    {
        return ($this->_countQuery !== null) ? $this->_countQuery : $this->_query;
    }


    /**
     * setCountQuery
     *
     * Defines the counter query to be used by pager
     *
     * @param Doctrine_Query     Accepts either a Doctrine_Query object or a string 
     *                           (which does the Doctrine_Query class creation).
     * @return void
     */
    public function setCountQuery($query)
    {
        if (is_string($query)) {
            $query = Doctrine_Query::create()->parseQuery($query);
        }

        $this->_countQuery = $query;

        $this->_setExecuted(false);
    }


    /**
     * getCountQueryParams
     *
     * Returns the params to be used by counter Doctrine_Query
     *
     * @return array     Doctrine_Query counter params
     */
    public function getCountQueryParams($defaultParams = array())
    {
        return ($this->_countQueryParams !== null) ? $this->_countQueryParams : $defaultParams;
    }


    /**
     * setCountQueryParams
     *
     * Defines the params to be used by counter Doctrine_Query
     *
     * @param array       Optional params to be used by counter Doctrine_Query. 
     *                    If not defined, the params passed to execute method will be used.
     * @param boolean     Optional argument that append the query param instead of overriding the existent ones.
     * @return void
     */
    public function setCountQueryParams($params = array(), $append = false)
    {
        if ($append && is_array($this->_countQueryParams)) {
            $this->_countQueryParams = array_merge($this->_countQueryParams, $params);
        } else {
            $this->_countQueryParams = $params;
        }

        $this->_setExecuted(false);
    }


    /**
     * execute
     *
     * Executes the query, populates the collection and then return it
     *
     * @param $params               Optional parameters to Doctrine_Query::execute
     * @param $hydrationMode        Hydration Mode of Doctrine_Query::execute 
     *                              returned ResultSet. Doctrine::Default is FETCH_RECORD
     * @return Doctrine_Collection  The root collection
     */
    public function execute($params = array(), $hydrationMode = Doctrine::FETCH_RECORD)
    {
        if (!$this->getExecuted()) {
            $this->_initialize($params);
        }

        return $this->getQuery()->execute($params, $hydrationMode);
    }
}
