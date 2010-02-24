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

namespace Doctrine\ORM\Event;

/**
 * Provides event arguments for the preFlush event.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       2.0
 * @version     $Revision$
 * @author      Roman Borschel <roman@code-factory.de>
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 */
class OnFlushEventArgs extends \Doctrine\Common\EventArgs
{
    /**
     * @var EntityManager
     */
    private $_em;
    
    //private $_entitiesToPersist = array();
    //private $_entitiesToRemove = array();
    
    public function __construct($em)
    {
        $this->_em = $em;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->_em;
    }
    
    /*
    public function addEntityToPersist($entity)
    {
        
    }
    
    public function addEntityToRemove($entity)
    {
        
    }
    
    public function addEntityToUpdate($entity)
    {
        
    }
    
    public function getEntitiesToPersist()
    {
        return $this->_entitiesToPersist;
    }
    */
}