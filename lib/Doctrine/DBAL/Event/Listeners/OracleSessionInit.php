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
 * <http://www.doctrine-project.org>.
*/

namespace Doctrine\DBAL\Event\Listeners;

use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\Common\EventSubscriber;

/**
 * Should be used when Oracle Server default enviroment does not match the Doctrine requirements.
 *
 * The following enviroment variables are required for the Doctrine default date format:
 *
 * NLS_TIME_FORMAT="HH24:MI:SS"
 * NLS_DATE_FORMAT="YYYY-MM-DD"
 * NLS_TIMESTAMP_FORMAT="YYYY-MM-DD HH24:MI:SS"
 * NLS_TIMESTAMP_TZ_FORMAT="YYYY-MM-DD HH24:MI:SS TZH:TZM"
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @version     $Revision$
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 */
class OracleSessionInit implements EventSubscriber
{
    protected $_defaultSessionVars = array(
        'NLS_TIME_FORMAT' => "HH24:MI:SS",
        'NLS_DATE_FORMAT' => "YYYY-MM-DD",
        'NLS_TIMESTAMP_FORMAT' => "YYYY-MM-DD HH24:MI:SS",
        'NLS_TIMESTAMP_TZ_FORMAT' => "YYYY-MM-DD HH24:MI:SS TZH:TZM",
    );

    /**
     * @param array $oracleSessionVars
     */
    public function __construct(array $oracleSessionVars = array())
    {
        $this->_defaultSessionVars = array_merge($this->_defaultSessionVars, $oracleSessionVars);
    }

    /**
     * @param ConnectionEventArgs $args
     * @return void
     */
    public function postConnect(ConnectionEventArgs $args)
    {
        if (count($this->_defaultSessionVars)) {
            array_change_key_case($this->_defaultSessionVars, \CASE_UPPER);
            $vars = array();
            foreach ($this->_defaultSessionVars AS $option => $value) {
                $vars[] = $option." = '".$value."'";
            }
            $sql = "ALTER SESSION SET ".implode(" ", $vars);
            $args->getConnection()->executeUpdate($sql);
        }
    }

    public function getSubscribedEvents()
    {
        return array(Events::postConnect);
    }
}
