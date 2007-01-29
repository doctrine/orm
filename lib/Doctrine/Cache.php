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
 * Doctrine_Cache
 *
 * @package     Doctrine
 * @subpackage  Doctrine_Cache
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Cache
{
      protected $_options = array('size'    => 1000,
                                  'lifetime'  => 3600,
                                  );

    protected $_queries = array();

    protected $_driver;
    /**
     * process
     *
     * @param string $query         sql query string
     * @return void
     */
    public function addQuery($query)
    {
        $this->queries[] = $query;
    }
    /**
     * save
     *
     * @return boolean
     */
    public function processAll()
    {
        $content = file_get_contents($this->_statsFile);
        $queries = explode("\n", $content);

        $stats   = array();

        foreach ($queries as $query) {
            if (isset($stats[$query])) {
                $stats[$query]++;
            } else {
                $stats[$query] = 1;
            }
        }
        sort($stats);

        $i = $this->_options['size'];
        
        while ($i--) {
            $element = next($stats);
            $query   = key($stats);
            $conn    = Doctrine_Manager::getConnection($element[1]);
            $data    = $conn->fetchAll($query);
            $this->_driver->save(serialize($data), $query, $this->_options['lifetime']);
        }
    }
    /**
     * flush
     *
     * adds all queries to stats file
     * @return void
     */
    public function flush()
    {
        file_put_contents($this->_statsFile, implode("\n", $this->queries));
    }
}
