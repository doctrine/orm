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
Doctrine::autoload('Doctrine_Expression');
/**
 * Doctrine_Expression_Sqlite
 *
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 */
class Doctrine_Expression_Oracle extends Doctrine_Expression {
    /**
     * Returns a series of strings concatinated
     *
     * concat() accepts an arbitrary number of parameters. Each parameter
     * must contain an expression
     *
     * @param string $arg1, $arg2 ... $argN     strings that will be concatinated.
     * @return string
     */
    public function concat($arg1, $arg2) {
        $args = func_get_args();

        $cols = $this->getIdentifiers( $args );
        return join( ' || ' , $cols );
    }
    /**
     * return string to call a function to get a substring inside an SQL statement
     *
     * Note: Not SQL92, but common functionality.
     *
     * @param string $value         an sql string literal or column name/alias
     * @param integer $position     where to start the substring portion
     * @param integer $length       the substring portion length
     * @return string               SQL substring function with given parameters
     */
    public function substring($value, $position, $length = null) {
        if($length !== null)
            return "SUBSTR($value, $position, $length)";

        return "SUBSTR($value, $position)";
    }
    /**
     * Return string to call a variable with the current timestamp inside an SQL statement
     * There are three special variables for current date and time:
     * - CURRENT_TIMESTAMP (date and time, TIMESTAMP type)
     * - CURRENT_DATE (date, DATE type)
     * - CURRENT_TIME (time, TIME type)
     *
     * @return string to call a variable with the current timestamp
     */
    public function now($type = 'timestamp') {
        switch ($type) {
        case 'date':
        case 'time':
        case 'timestamp':
        default:
            return 'TO_CHAR(CURRENT_TIMESTAMP, \'YYYY-MM-DD HH24:MI:SS\')';
        }
    }
    /**
     * random
     *
     * @return string           an oracle SQL string that generates a float between 0 and 1
     */
    function random() {
        return 'dbms_random.value';
    }
}
