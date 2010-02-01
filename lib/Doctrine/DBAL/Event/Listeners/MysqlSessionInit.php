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
 * MySQL Session Init Event Subscriber which allows to set the Client Encoding of the Connection
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @version     $Revision$
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 */
class MysqlSessionInit implements EventSubscriber
{
    /**
     * @var string
     */
    private $_charset;

    /**
     * @var string
     */
    private $_collation;

    /**
     * Configure Charset and Collation options of MySQL Client for each Connection
     *
     * @param string $charset
     * @param string $collation
     */
    public function __construct($charset = 'utf8', $collation = false)
    {
        $this->_charset = $charset;
        $this->_collation = $collation;
    }

    /**
     * @param ConnectionEventArgs $args
     * @return void
     */
    public function postConnect(ConnectionEventArgs $args)
    {
        $collation = ($this->_collation) ? " COLLATE ".$this->_collation : "";
        $args->getConnection()->executeUpdate("SET NAMES ".$this->_charset . $collation);
    }

    public function getSubscribedEvents()
    {
        return array(Events::postConnect);
    }
}