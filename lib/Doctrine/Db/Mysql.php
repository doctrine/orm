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
Doctrine::autoload('Doctrine_Db');
/**
 * @package     Doctrine
 * @url         http://www.phpdoctrine.com
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Konsta Vesterinen
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @version     $Id$
 */
class Doctrine_Db_Mysql extends Doctrine_Db {
    protected static $errorCodeMap = array(
                                      1004 => Doctrine_Db::ERR_CANNOT_CREATE,
                                      1005 => Doctrine_Db::ERR_CANNOT_CREATE,
                                      1006 => Doctrine_Db::ERR_CANNOT_CREATE,
                                      1007 => Doctrine_Db::ERR_ALREADY_EXISTS,
                                      1008 => Doctrine_Db::ERR_CANNOT_DROP,
                                      1022 => Doctrine_Db::ERR_ALREADY_EXISTS,
                                      1044 => Doctrine_Db::ERR_ACCESS_VIOLATION,
                                      1046 => Doctrine_Db::ERR_NODBSELECTED,
                                      1048 => Doctrine_Db::ERR_CONSTRAINT,
                                      1049 => Doctrine_Db::ERR_NOSUCHDB,
                                      1050 => Doctrine_Db::ERR_ALREADY_EXISTS,
                                      1051 => Doctrine_Db::ERR_NOSUCHTABLE,
                                      1054 => Doctrine_Db::ERR_NOSUCHFIELD,
                                      1061 => Doctrine_Db::ERR_ALREADY_EXISTS,
                                      1062 => Doctrine_Db::ERR_ALREADY_EXISTS,
                                      1064 => Doctrine_Db::ERR_SYNTAX,
                                      1091 => Doctrine_Db::ERR_NOT_FOUND,
                                      1100 => Doctrine_Db::ERR_NOT_LOCKED,
                                      1136 => Doctrine_Db::ERR_VALUE_COUNT_ON_ROW,
                                      1142 => Doctrine_Db::ERR_ACCESS_VIOLATION,
                                      1146 => Doctrine_Db::ERR_NOSUCHTABLE,
                                      1216 => Doctrine_Db::ERR_CONSTRAINT,
                                      1217 => Doctrine_Db::ERR_CONSTRAINT,
                                      );
}
