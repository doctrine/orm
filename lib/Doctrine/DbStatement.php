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
class Doctrine_DbStatement extends PDOStatement {
    /**
     * @param Doctrine_Db $dbh        Doctrine Database Handler
     */
    private $dbh;
    /**
     * @param Doctrine_Db $dbh
     */
    private function __construct(Doctrine_Db $dbh) {
        $this->dbh = $dbh;
    }
    /**
     * @param array $params
     */
    public function execute(array $params = array()) {

        $time     = microtime();
        try {
            $result   = parent::execute($params);
        } catch(PDOException $e) {
            throw new Doctrine_Exception($this->queryString." ".$e->__toString());
        }
        $exectime = (microtime() - $time);
        $this->dbh->addExecTime($exectime);

        return $result;
    }
}

