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
 * Doctrine_Db_Event
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Db_Event {
    const QUERY     = 1;
    const EXEC      = 2;
    const EXECUTE   = 3;
    const PREPARE   = 4;
    const BEGIN     = 5;
    const COMMIT    = 6;
    const ROLLBACK  = 7;
    protected $invoker;

    protected $query;

    protected $type;

    protected $startedMicrotime;

    protected $endedMicrotime;

    public function __construct($invoker, $type, $query = null) {
        $this->invoker = $invoker;
        $this->type    = $type;
        $this->query   = $query;
    }
    public function getQuery() {
        return $this->query;
    }
    public function getType() {
        return $this->type;
    }

    public function start() {
        $this->startedMicrotime = microtime(true);
    }
    public function hasEnded() {
        return ($this->endedMicrotime != null);
    }
    public function end() {
        $this->endedMicrotime = microtime(true);
    }
    public function getInvoker() {
        return $this->invoker;
    }
    /**
     * Get the elapsed time (in microseconds) that the event ran.  If the event has
     * not yet ended, return false.
     *
     * @return mixed
     */
    public function getElapsedSecs() {
        if (is_null($this->endedMicrotime)) {
            return false;
        }
        return ($this->endedMicrotime - $this->startedMicrotime);
    }

}
