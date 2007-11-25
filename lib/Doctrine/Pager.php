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
     * @var Doctrine_Query $query     Doctrine_Query object related to the pager
     */
    protected $query;

    /**
     * @var int $nbResults     Number of results found
     */
    protected $nbResults;

    /**
     * @var int $maxPerPage     Maximum number of itens per page
     */
    protected $maxPerPage;

    /**
     * @var int $page     Current page
     */
    protected $page;

    /**
     * @var int $lastPage     Last page (total of pages)
     */
    protected $lastPage;



    /**
     * __construct
     *
     * @param mixed $query     Accepts either a Doctrine_Query object or a string 
	 *                        (which does the Doctrine_Query class creation).
     * @param int $page     Current page
     * @param int $maxPerPage     Maximum itens per page
     * @return void
     */
    public function __construct( $query, $page, $maxPerPage = 0 )
    {
        $this->setQuery($query);

        $this->_setMaxPerPage($maxPerPage);
        $this->_setPage($page);

        $this->initialize();
    }


    /**
     * initialize
     *
     * Initialize Pager object calculating number of results
     *
     * @return void
     */
    protected function initialize()
    {
        // retrieve the number of items found
		$count = $this->getQuery()->count();
        $this->setNbResults($count);

        $this->adjustOffset();
    }


    /**
     * adjustOffset
     *
     * Adjusts last page of Doctrine_Pager, offset and limit of Doctrine_Query associated
     *
     * @return void
     */
    protected function adjustOffset()
    {
        // Define new total of pages
		$this->setLastPage(
            max(1, ceil($this->getNbResults() / $this->getMaxPerPage()))
        );
        $offset = ($this->getPage() - 1) * $this->getMaxPerPage();

		// Assign new offset and limit to Doctrine_Query object
        $p = $this->getQuery();
        $p->offset($offset);
        $p->limit($this->getMaxPerPage());
    }


    /**
     * getNbResults
     *
     * Returns the number of results found
     *
     * @return int        the number of results found
     */
    public function getNbResults()
    {
        return $this->nbResults;
    }


    /**
     * setNbResults
     *
     * Defines the number of total results on initial query
     *
     * @param $nb       Number of results found on initial query fetch
     * @return void
     */
    protected function setNbResults($nb)
    {
        $this->nbResults = $nb;
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
        return $this->lastPage;
    }


    /**
     * setLastPage
     *
     * Defines the last page (total of pages)
     *
     * @param $page       last page (total of pages)
     * @return void
     */
    protected function setLastPage($page)
    {
        $this->lastPage = $page;

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
        return $this->page;
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
        return $this->getNbResults() > $this->getMaxPerPage();
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
        $this->adjustOffset();
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
        $this->page = ($page <= 0) ? 1 : $page;
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
        return $this->maxPerPage;
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
        $this->adjustOffset();
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
            $this->maxPerPage = $max;
        } else if ($max == 0) {
            $this->maxPerPage = 25;
        } else {
            $this->maxPerPage = abs($max);
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
        return $this->query;
    }


    /**
     * setQuery
     *
     * Defines the maximum number of itens per page
     *
     * @param $query     Accepts either a Doctrine_Query object or a string 
	 *                   (which does the Doctrine_Query class creation).
     * @return void
     */
    protected function setQuery($query)
    {
        if (is_string($query)) {
            $query = Doctrine_Query::create()->parseQuery($query);
        }

        $this->query = $query;
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
