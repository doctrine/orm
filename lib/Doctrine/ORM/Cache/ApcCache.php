<?php
/*
 *  $Id: Apc.php 4910 2008-09-12 08:51:56Z romanb $
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
 * APC cache driver.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @version     $Revision: 4910 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class ApcCache implements Cache
{
    /**
     * {@inheritdoc}
     */
    public function __construct()
    {      
        if ( ! extension_loaded('apc')) {
            \Doctrine\Common\DoctrineException::updateMe('The apc extension must be loaded in order to use the ApcCache.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($id) 
    {
        return apc_fetch($id);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($id) 
    {
        return apc_fetch($id) === false ? false : true;
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $lifeTime = false)
    {
        return (bool) apc_store($id, $data, $lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id) 
    {
        return apc_delete($id);
    }
}