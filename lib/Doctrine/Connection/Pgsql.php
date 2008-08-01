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
 * <http://www.phpdoctrine.org>.
 */

#namespace Doctrine::DBAL::Connections;

/**
 * PgsqlConnection
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @version     $Revision$
 * @link        www.phpdoctrine.org
 * @since       1.0
 */
class Doctrine_Connection_Pgsql extends Doctrine_Connection_Common
{
    /**
     * @var string $driverName                  the name of this connection driver
     */
    protected $driverName = 'Pgsql';

    /**
     * the constructor
     *
     * @param Doctrine_Manager $manager
     * @param PDO $pdo                          database handle
     */
    public function __construct(array $params)
    {
        parent::__construct($params);
    }

    /**
     * Set the charset on the current connection
     *
     * @param string    charset
     *
     * @return void
     */
    public function setCharset($charset)
    {
        $query = 'SET NAMES '.$this->dbh->quote($charset);
        $this->exec($query);
    }

    /**
     * convertBoolean
     * some drivers need the boolean values to be converted into integers
     * when using DQL API
     *
     * This method takes care of that conversion
     *
     * @param array $item
     * @return void
     */
    public function convertBooleans($item)
    {
        if (is_array($item)) {
            foreach ($item as $key => $value) {
                if (is_bool($value) || is_numeric($item)) {
                    $item[$key] = ($value) ? 'true' : 'false';
                }
            }
        } else {
           if (is_bool($item) || is_numeric($item)) {
               $item = ($item) ? 'true' : 'false';
           }
        }
        return $item;
    }

    /**
     * return version information about the server
     *
     * @param string $native    determines if the raw version string should be returned
     * @return array|string     an array or string with version information
     */
    public function getServerVersion($native = false)
    {
        $query = 'SHOW SERVER_VERSION';

        $serverInfo = $this->fetchOne($query);

        if ( ! $native) {
            $tmp = explode('.', $serverInfo, 3);

            if (empty($tmp[2]) && isset($tmp[1])
                && preg_match('/(\d+)(.*)/', $tmp[1], $tmp2)
            ) {
                $serverInfo = array(
                    'major' => $tmp[0],
                    'minor' => $tmp2[1],
                    'patch' => null,
                    'extra' => $tmp2[2],
                    'native' => $serverInfo,
                );
            } else {
                $serverInfo = array(
                    'major' => isset($tmp[0]) ? $tmp[0] : null,
                    'minor' => isset($tmp[1]) ? $tmp[1] : null,
                    'patch' => isset($tmp[2]) ? $tmp[2] : null,
                    'extra' => null,
                    'native' => $serverInfo,
                );
            }
        }
        return $serverInfo;
    }
}