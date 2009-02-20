<?php
/*
 *  $Id: Array.php 4910 2008-09-12 08:51:56Z romanb $
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

namespace Doctrine\ORM\Cache;

/**
 * Array cache driver.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @version     $Revision: 4910 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class ArrayCache implements Cache
{
    /**
     * @var array $data
     */
    private $data;

    /**
     * {@inheritdoc}
     */
    public function fetch($id) 
    { 
        if (isset($this->data[$id])) {
            return $this->data[$id];
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function contains($id)
    {
        return isset($this->data[$id]);
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $lifeTime = false)
    {
        $this->data[$id] = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        unset($this->data[$id]);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll()
    {
        $this->data = array();
    }

    /**
     * count
     *
     * @return integer
     */
    public function count() 
    {
        return count($this->data);
    }
}