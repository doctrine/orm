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
 * %s
 *
 * @package     Doctrine
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     1.0
 */
class Doctrine_Query_MultipleAggregateValue_TestCase extends Doctrine_UnitTestCase 
{
	public function setUp()
	{
		$user = new User();
		$user->name = 'jon';
		
		$user->Album[0] = new Album();
		$user->Album[1] = new Album();
		$user->Album[2] = new Album();
		
		$user->Book[0] = new Book();
		$user->Book[1] = new Book();
		$user->save();
	}
	
	public function testMultipleAggregateValues()
	{
		$query = new Doctrine_Query();
		$query->select('u.*, COUNT(DISTINCT b.id) num_books, COUNT(DISTINCT a.id) num_albums');
		$query->from('User u');
		$query->leftJoin('u.Album a, u.Book b');
		$query->where("u.name = 'jon'");
		$query->limit(1);
		
		$user = $query->execute()->getFirst();
		
		try {
			$name = $user->name;
			$num_albums = $user->Album[0]->num_albums;
			$num_books = $user->Book[0]->num_books;	
		} catch(Doctrine_Exception $e) {
			$this->fail();
		}
		
		$this->assertEqual($num_albums, 3);
		$this->assertEqual($num_books, 2);
	}
}