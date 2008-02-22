<?php
/*
 *  $Id: RebuildDb.php 2761 2007-10-07 23:42:29Z zYne $
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

/**
 * Doctrine_Task_RebuildDb
 *
 * @package     Doctrine
 * @subpackage  Task
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision: 2761 $
 * @author      Jonathan H. Wage <jwage@mac.com>
 */
class Doctrine_Task_RebuildDb extends Doctrine_Task
{
    public $description          =   'Drops and re-creates databases',
           $requiredArguments    =   array(),
           $optionalArguments    =   array();
    
    public function __construct($dispatcher = null)
    {
        parent::__construct($dispatcher);
        
        $this->dropDb = new Doctrine_Task_DropDb($this->dispatcher);
        $this->buildAll = new Doctrine_Task_BuildAll($this->dispatcher);
        
        $this->requiredArguments = array_merge($this->requiredArguments, $this->dropDb->requiredArguments, $this->buildAll->requiredArguments);
        $this->optionalArguments = array_merge($this->optionalArguments, $this->dropDb->optionalArguments, $this->buildAll->optionalArguments);
    }
    
    public function execute()
    {
        $this->dropDb->setArguments($this->getArguments());
        $this->dropDb->execute();
        
        $this->buildAll->setArguments($this->getArguments());
        $this->buildAll->execute();
    }
}