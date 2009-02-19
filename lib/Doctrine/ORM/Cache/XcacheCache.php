<?php
/*
 *  $Id: Xcache.php  2007-11-19 14:47:59Z demongloom $
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

namespace Doctrine\ORM\Cache;

/**
 * Xcache cache driver.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @version     $Revision: $
 * @author      Dmitry Bakaleinik (dima@snaiper.net)
 */
class XcacheCache implements Cache
{
    /**
     * {@inheritdoc}
     */
    public function __construct()
    {      
        if ( ! extension_loaded('xcache')) {
            throw \Doctrine\Common\DoctrineException::updateMe('In order to use Xcache driver, the xcache extension must be loaded.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($id) 
    {
        return $this->contains($id) ? xcache_get($id) : false;
    }

    /**
     * {@inheritdoc}
     */
    public function contains($id) 
    {
        return xcache_isset($id);
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $lifeTime = false)
    {
        return xcache_set($id, $data, $lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id) 
    {
        return xcache_unset($id);       
    }
}