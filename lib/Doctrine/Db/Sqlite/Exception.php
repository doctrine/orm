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
Doctrine::autoload('Doctrine_Db_Exception');
/**
 * Doctrine_Db_Sqlite_Exception
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @since       1.0
 * @version     $Revision$
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 */
class Doctrine_Db_Sqlite_Exception extends Doctrine_Db_Exception { 
    /**
     * @var array $errorRegexps         an array that is used for determining portable
     *                                  error code from a native database error message
     */
    protected static $errorRegexps = array(
                              '/^no such table:/'                    => Doctrine_Db::ERR_NOSUCHTABLE,
                              '/^no such index:/'                    => Doctrine_Db::ERR_NOT_FOUND,
                              '/^(table|index) .* already exists$/'  => Doctrine_Db::ERR_ALREADY_EXISTS,
                              '/PRIMARY KEY must be unique/i'        => Doctrine_Db::ERR_CONSTRAINT,
                              '/is not unique/'                      => Doctrine_Db::ERR_CONSTRAINT,
                              '/columns .* are not unique/i'         => Doctrine_Db::ERR_CONSTRAINT,
                              '/uniqueness constraint failed/'       => Doctrine_Db::ERR_CONSTRAINT,
                              '/may not be NULL/'                    => Doctrine_Db::ERR_CONSTRAINT_NOT_NULL,
                              '/^no such column:/'                   => Doctrine_Db::ERR_NOSUCHFIELD,
                              '/column not present in both tables/i' => Doctrine_Db::ERR_NOSUCHFIELD,
                              '/^near ".*": syntax error$/'          => Doctrine_Db::ERR_SYNTAX,
                              '/[0-9]+ values for [0-9]+ columns/i'  => Doctrine_Db::ERR_VALUE_COUNT_ON_ROW,
                              );

    /**
     * This method checks if native error code/message can be 
     * converted into a portable code and then adds this 
     * portable error code to errorInfo array and returns the modified array
     *
     * the portable error code is added at the end of array
     *
     * @param array $errorInfo      error info array
     * @since 1.0
     * @return array
     */
    public function processErrorInfo(array $errorInfo) {
        foreach (self::$errorRegexps as $regexp => $code) {
            if (preg_match($regexp, $errorInfo[2])) {
                $errorInfo[3] = $code;
                break;
            }
        }

        return $errorInfo;
    }
}
