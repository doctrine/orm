<?php
/*
 *  $Id: Memcache.php 4910 2008-09-12 08:51:56Z romanb $
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

namespace Doctrine\Common\Cache;

/**
 * Memcache cache driver.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision: 4910 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class MemcacheCache implements Cache
{
    /**
     * @var Memcache $_memcache     memcache object
     */
    private $_memcache;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {      
        if ( ! extension_loaded('memcache')) {
            throw \Doctrine\Common\DoctrineException::updateMe('In order to use Memcache driver, the memcache extension must be loaded.');
        }
    }

    /**
     * Sets the memcache instance to use.
     *
     * @param Memcache $memcache
     */
    public function setMemcache(Memcache $memcache)
    {
        $this->_memcache = $memcache;
    }

    /**
     * Gets the memcache instance used by the cache.
     *
     * @return Memcache
     */
    public function getMemcache()
    {
        return $this->_memcache;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($id) 
    {
        return $this->_memcache->get($id);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($id) 
    {
        return (bool) $this->_memcache->get($id);
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $lifeTime = false)
    {
        return $this->_memcache->set($id, $data, 0, $lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id) 
    {
        return $this->_memcache->delete($id);
    }
}