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
 * Doctrine_Db_Exception
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Db_Exception extends Doctrine_Exception { 
    /**
     * @var array $errorMessages        an array containing messages for portable error codes
     */
    protected static $errorMessages = array(
                Doctrine_Db::ERR                    => 'unknown error',
                Doctrine_Db::ERR_ALREADY_EXISTS     => 'already exists',
                Doctrine_Db::ERR_CANNOT_CREATE      => 'can not create',
                Doctrine_Db::ERR_CANNOT_ALTER       => 'can not alter',
                Doctrine_Db::ERR_CANNOT_REPLACE     => 'can not replace',
                Doctrine_Db::ERR_CANNOT_DELETE      => 'can not delete',
                Doctrine_Db::ERR_CANNOT_DROP        => 'can not drop',
                Doctrine_Db::ERR_CONSTRAINT         => 'constraint violation',
                Doctrine_Db::ERR_CONSTRAINT_NOT_NULL=> 'null value violates not-null constraint',
                Doctrine_Db::ERR_DIVZERO            => 'division by zero',
                Doctrine_Db::ERR_INVALID            => 'invalid',
                Doctrine_Db::ERR_INVALID_DATE       => 'invalid date or time',
                Doctrine_Db::ERR_INVALID_NUMBER     => 'invalid number',
                Doctrine_Db::ERR_MISMATCH           => 'mismatch',
                Doctrine_Db::ERR_NODBSELECTED       => 'no database selected',
                Doctrine_Db::ERR_NOSUCHFIELD        => 'no such field',
                Doctrine_Db::ERR_NOSUCHTABLE        => 'no such table',
                Doctrine_Db::ERR_NOT_CAPABLE        => 'MDB2 backend not capable',
                Doctrine_Db::ERR_NOT_FOUND          => 'not found',
                Doctrine_Db::ERR_NOT_LOCKED         => 'not locked',
                Doctrine_Db::ERR_SYNTAX             => 'syntax error',
                Doctrine_Db::ERR_UNSUPPORTED        => 'not supported',
                Doctrine_Db::ERR_VALUE_COUNT_ON_ROW => 'value count on row',
                Doctrine_Db::ERR_INVALID_DSN        => 'invalid DSN',
                Doctrine_Db::ERR_CONNECT_FAILED     => 'connect failed',
                Doctrine_Db::ERR_NEED_MORE_DATA     => 'insufficient data supplied',
                Doctrine_Db::ERR_EXTENSION_NOT_FOUND=> 'extension not found',
                Doctrine_Db::ERR_NOSUCHDB           => 'no such database',
                Doctrine_Db::ERR_ACCESS_VIOLATION   => 'insufficient permissions',
                Doctrine_Db::ERR_LOADMODULE         => 'error while including on demand module',
                Doctrine_Db::ERR_TRUNCATED          => 'truncated',
                Doctrine_Db::ERR_DEADLOCK           => 'deadlock detected',
                );

    /**
     * Return a textual error message for a Doctrine_Db error code
     *
     * @param   int|array   integer error code,
                                null to get the current error code-message map,
                                or an array with a new error code-message map
     *
     * @return  string  error message, or false if the error code was
     *                  not recognized
     */
    public function errorMessage($value = null) {
        return isset(self::$errorMessages[$value]) ?
           self::$errorMessages[$value] : self::$errorMessages[Doctrine_Db::ERR];
    }
}
