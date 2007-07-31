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
 * <http://www.phpdoctrine.com>.
 */

/**
 * Doctrine_Query_SelectExpression_TestCase
 * This test case is used for testing DQL SELECT expressions functionality
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Query_SelectExpression_TestCase extends Doctrine_UnitTestCase 
{
    public function testAdditionExpression()
    {
        $query = new Doctrine_Query();
        $query->select('u.*, (u.id + u.id) addition');
        $query->from('User u');
        
        try {
            $users = $query->execute();
        } catch(Exception $e) {
            $this->fail();
        }
    }
    
    public function testSubtractionExpression()
    {
        $query = new Doctrine_Query();
        $query->select('u.*, (u.id - u.id) subtraction');
        $query->from('User u');
        
        try {
            $users = $query->execute();
        } catch(Exception $e) {
            $this->fail();
        }
    }
    
    public function testDivisionExpression()
    {
        $query = new Doctrine_Query();
        $query->select('u.*, (u.id/u.id) division');
        $query->from('User u');
        
        try {
            $users = $query->execute();
        } catch(Exception $e) {
            $this->fail();
        } 
    }
    
    public function testMultiplicationExpression()
    {
        $query = new Doctrine_Query();
        $query->select('u.*, (u.id * u.id) multiplication');
        $query->from('User u');
        
        try {
            $users = $query->execute();
        } catch(Exception $e) {
            $this->fail();
        } 
    }
    
    public function testOrderByExpression()
    {
        $query = new Doctrine_Query();
        $query->select('u.*, (u.id * u.id) multiplication');
        $query->from('User u');
        $query->orderby('multiplication asc');
        
        try {
            $users = $query->execute();
        } catch(Exception $e) {
            $this->fail();
        }
    }
}