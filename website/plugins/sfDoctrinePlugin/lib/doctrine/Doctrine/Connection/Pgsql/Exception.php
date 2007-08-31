<?php
/*
 *  $Id: Exception.php 1080 2007-02-10 18:17:08Z romanb $
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
Doctrine::autoload('Doctrine_Connection_Exception');
/**
 * Doctrine_Connection_Pgsql_Exception
 *
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Paul Cooper <pgc@ucecom.com> (PEAR MDB2 Pgsql driver)
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @since       1.0
 * @version     $Revision: 1080 $
 */
class Doctrine_Connection_Pgsql_Exception extends Doctrine_Connection_Exception
{
    /**
     * @var array $errorRegexps         an array that is used for determining portable
     *                                  error code from a native database error message
     */
    protected static $errorRegexps = array(
                                    '/parser: parse error at or near/i'
                                        => Doctrine::ERR_SYNTAX,
                                    '/syntax error at/'
                                        => Doctrine::ERR_SYNTAX,
                                    '/column reference .* is ambiguous/i'
                                        => Doctrine::ERR_SYNTAX,
                                    '/column .* (of relation .*)?does not exist/i'
                                        => Doctrine::ERR_NOSUCHFIELD,
                                    '/attribute .* not found|relation .* does not have attribute/i'
                                        => Doctrine::ERR_NOSUCHFIELD,
                                    '/column .* specified in USING clause does not exist in (left|right) table/i'
                                        => Doctrine::ERR_NOSUCHFIELD,
                                    '/(relation|sequence|table).*does not exist|class .* not found/i'
                                        => Doctrine::ERR_NOSUCHTABLE,
                                    '/index .* does not exist/'
                                        => Doctrine::ERR_NOT_FOUND,
                                    '/relation .* already exists/i'
                                        => Doctrine::ERR_ALREADY_EXISTS,
                                    '/(divide|division) by zero$/i'
                                        => Doctrine::ERR_DIVZERO,
                                    '/pg_atoi: error in .*: can\'t parse /i'
                                        => Doctrine::ERR_INVALID_NUMBER,
                                    '/invalid input syntax for( type)? (integer|numeric)/i'
                                        => Doctrine::ERR_INVALID_NUMBER,
                                    '/value .* is out of range for type \w*int/i'
                                        => Doctrine::ERR_INVALID_NUMBER,
                                    '/integer out of range/i'
                                        => Doctrine::ERR_INVALID_NUMBER,
                                    '/value too long for type character/i'
                                        => Doctrine::ERR_INVALID,
                                    '/permission denied/'
                                        => Doctrine::ERR_ACCESS_VIOLATION,
                                    '/violates [\w ]+ constraint/'
                                        => Doctrine::ERR_CONSTRAINT,
                                    '/referential integrity violation/'
                                        => Doctrine::ERR_CONSTRAINT,
                                    '/violates not-null constraint/'
                                        => Doctrine::ERR_CONSTRAINT_NOT_NULL,
                                    '/more expressions than target columns/i'
                                        => Doctrine::ERR_VALUE_COUNT_ON_ROW,
                                );
    /**
     * This method checks if native error code/message can be
     * converted into a portable code and then adds this
     * portable error code to $portableCode field
     *
     * the portable error code is added at the end of array
     *
     * @param array $errorInfo      error info array
     * @since 1.0
     * @see Doctrine::ERR_* constants
     * @see Doctrine_Connection::$portableCode
     * @return boolean              whether or not the error info processing was successfull
     *                              (the process is successfull if portable error code was found)
     */
    public function processErrorInfo(array $errorInfo)
    {
        foreach (self::$errorRegexps as $regexp => $code) {
            if (preg_match($regexp, $errorInfo[2])) {
                $this->portableCode = $code;
                return true;
            }
        }
        return false;
    }
}
