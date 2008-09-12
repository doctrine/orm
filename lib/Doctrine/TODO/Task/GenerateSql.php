<?php
/*
 *  $Id: GenerateSql.php 2761 2007-10-07 23:42:29Z zYne $
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
 * Doctrine_Task_GenerateSql
 *
 * @package     Doctrine
 * @subpackage  Task
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision: 2761 $
 * @author      Jonathan H. Wage <jwage@mac.com>
 */
class Doctrine_Task_GenerateSql extends Doctrine_Task
{
    public $description          =   'Generate sql for all existing database connections.',
           $requiredArguments    =   array('models_path'    =>  'Specify complete path to your Doctrine_Record definitions.',
                                           'sql_path'       =>  'Path to write the generated sql.'),
           $optionalArguments    =   array();
    
    public function execute()
    {
        if (is_dir($this->getArgument('sql_path'))) {
            $path = $this->getArgument('sql_path') . DIRECTORY_SEPARATOR . 'schema.sql';
        } else if (is_file($this->getArgument('sql_path'))) {
            $path = $this->getArgument('sql_path');
        } else {
            throw new Doctrine_Task_Exception('Invalid sql path.');
        }
        
        $sql = Doctrine::generateSqlFromModels($this->getArgument('models_path'));
        
        file_put_contents($path, $sql);
        
        $this->dispatcher->notify('Generated SQL successfully for models');
    }
}