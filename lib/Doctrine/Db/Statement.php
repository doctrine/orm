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
 * Doctrine_Db_Statement
 *
 * @author      Konsta Vesterinen
 * @license     LGPL
 * @package     Doctrine
 */
class Doctrine_Db_Statement extends PDOStatement { 
    protected $dbh;

    protected $querySequence;
    
    protected $baseSequence;

    protected $executed = false;

    protected function __construct($dbh) {
        $this->dbh = $dbh;
        $this->baseSequence  = $this->querySequence = $this->dbh->getQuerySequence();
    }

    public function getQuerySequence() {
        return $this->querySequence;
    }
    public function getBaseSequence() {
        return $this->baseSequence;
    }
    public function getQuery() {
        return $this->queryString;
    }
    public function isExecuted($executed = null) {
        if($executed === null)
            return $this->executed;

        $this->executed = (bool) $executed;
    }

    public function execute(array $params = array()) {
        $event = new Doctrine_Db_Event($this, Doctrine_Db_Event::EXECUTE, $this->queryString);

        $this->dbh->getListener()->onPreExecute($event);

        $ret = parent::execute($params);

        $this->dbh->getListener()->onExecute($event);



        return $this;
    }
}
