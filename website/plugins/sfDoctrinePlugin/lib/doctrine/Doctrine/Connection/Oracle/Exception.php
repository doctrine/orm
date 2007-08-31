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
 * Doctrine_Connection_Oracle_Exception
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @since       1.0
 * @version     $Revision: 1080 $
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 */
class Doctrine_Connection_Oracle_Exception extends Doctrine_Connection_Exception
{
    /**
     * @var array $errorCodeMap         an array that is used for determining portable
     *                                  error code from a native database error code
     */
    protected static $errorCodeMap = array(
                                      1    => Doctrine::ERR_CONSTRAINT,
                                      900  => Doctrine::ERR_SYNTAX,
                                      904  => Doctrine::ERR_NOSUCHFIELD,
                                      913  => Doctrine::ERR_VALUE_COUNT_ON_ROW,
                                      921  => Doctrine::ERR_SYNTAX,
                                      923  => Doctrine::ERR_SYNTAX,
                                      942  => Doctrine::ERR_NOSUCHTABLE,
                                      955  => Doctrine::ERR_ALREADY_EXISTS,
                                      1400 => Doctrine::ERR_CONSTRAINT_NOT_NULL,
                                      1401 => Doctrine::ERR_INVALID,
                                      1407 => Doctrine::ERR_CONSTRAINT_NOT_NULL,
                                      1418 => Doctrine::ERR_NOT_FOUND,
                                      1476 => Doctrine::ERR_DIVZERO,
                                      1722 => Doctrine::ERR_INVALID_NUMBER,
                                      2289 => Doctrine::ERR_NOSUCHTABLE,
                                      2291 => Doctrine::ERR_CONSTRAINT,
                                      2292 => Doctrine::ERR_CONSTRAINT,
                                      2449 => Doctrine::ERR_CONSTRAINT,
                                      );
    /**
     * This method checks if native error code/message can be
     * converted into a portable code and then adds this
     * portable error code to $portableCode field
     *
     * @param array $errorInfo      error info array
     * @since 1.0
     * @return boolean              whether or not the error info processing was successfull
     *                              (the process is successfull if portable error code was found)
     */
    public function processErrorInfo(array $errorInfo)
    {
        $code = $errorInfo[1];
        if (isset(self::$errorCodeMap[$code])) {
            $this->portableCode = self::$errorCodeMap[$code];
            return true;
        }
        return false;
    }
}
