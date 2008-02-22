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

/**
 * Doctrine_Query_ApplyInheritance_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Query_ApplyInheritance_TestCase extends Doctrine_UnitTestCase 
{
    public function prepareData() 
    {
        
    }
    
    public function prepareTables() 
    {
        $this->tables = array('InheritanceDeal', 'InheritanceEntityUser', 'InheritanceUser');
        
        parent::prepareTables();
    }
    
    public function testApplyInheritance()
    {
        $query = new Doctrine_Query();
        $query->from('InheritanceDeal deal, deal.Users usrs');
        $query->where('usrs.id = 1');
        
        $sql = 'SELECT i.id AS i__id, i.name AS i__name, i2.id AS i2__id, i2.username AS i2__username FROM inheritance_deal i LEFT JOIN inheritance_entity_user i3 ON i.id = i3.entity_id LEFT JOIN inheritance_user i2 ON i2.id = i3.user_id WHERE i2.id = 1 AND (i3.type = 1 OR i3.type IS NULL)';

        $this->assertEqual($sql, $query->getSql());
    }
}
