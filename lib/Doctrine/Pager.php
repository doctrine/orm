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
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Pager
{
    /**
     * @var Doctrine_Query $_query      Doctrine_Query object related to the pager
     */
    protected $_query;

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
        $this->_setQuery($query);

        $this->_setMaxPerPage($maxPerPage);
        $this->_setPage($page);

        $this->_initialize();
    }


    /**
     * _initialize
     *
     * Initialize Pager object calculating number of results
     *
     * @return void
     */
    protected function _initialize()
    {
        // retrieve the number of items found
		$count = $this->getQuery()->count();
        $this->_setNumResults($count);

        $this->_adjustOffset();
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


    /**
     * getNumResults
     *
     * Returns the number of results found
     *
     * @return int        the number of results found
     */
    public function getNumResults()
    {
        return $this->_numResults;
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
        return $this->_lastPage;
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
        return min($this->getPage() + 1, $this->getLastPage());
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
        return max($this->getPage() - 1, $this->getFirstPage());
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
        return $this->getNumResults() > $this->getMaxPerPage();
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
        $this->_adjustOffset();
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
        $this->_setMaxPerPage($max);
        $this->_adjustOffset();
    }


    /**
     * _setMaxPerPage
     *
     * Defines the maximum number of itens per page
     *
     * @param $max       maximum number of itens per page
     * @return void
     */
    private function _setMaxPerPage($max)
    {
        if ($max > 0) {
            $this->_maxPerPage = $max;
        } else if ($max == 0) {
            $this->_maxPerPage = 25;
        } else {
            $this->_maxPerPage = abs($max);
        }
    }


    /**
     * getQuery
     *
     * Returns the Doctrine_Query object related to the pager
     *
     * @return Doctrine_Query        Doctrine_Query object related to the pager
     */
    public function getQuery()
    {
        return $this->_query;
    }


    /**
     * _setQuery
     *
     * Defines the maximum number of itens per page
     *
     * @param $query     Accepts either a Doctrine_Query object or a string 
	 *                   (which does the Doctrine_Query class creation).
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
     * execute
     * executes the query and populates the data set
     *
     * @param $params        Optional parameters to Doctrine_Query::execute
     * @param $hydrationMode        Hyddration Mode of Doctrine_Query::execute 
	 *                              returned ResultSet. Doctrine::Default is FETCH_RECORD
     * @return Doctrine_Collection            the root collection
     */
    public function execute($params = array(), $hydrationMode = Doctrine::FETCH_RECORD)
    {
        return $this->getQuery()->execute($params, $hydrationMode);
    }
}
