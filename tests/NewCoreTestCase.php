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


require_once('../draft/new-core/Record.php');
require_once('../draft/new-core/Hydrate.php');
require_once('../draft/new-core/Query.php');
require_once('../draft/new-core/Collection.php');

/**
 * Doctrine_NewCore_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */


class Doctrine_NewCore_TestCase extends Doctrine_UnitTestCase 
{
    public function testHydrate() 
    {
        $h = new Doctrine_Hydrate_Mock();
        $h->setData(array(
                        array(
                            'e' => array('id' => 1, 'name' => 'zYne'),
                            'p' => array('id' => 1, 'phonenumber' => '123 123', 'user_id' => 1)
                            ),
                        array(
                            'e' => array('id' => 2, 'name' => 'John'),
                            'p' => array('id' => 2, 'phonenumber' => '222 222', 'user_id' => 2)
                            ),
                        array(
                            'e' => array('id' => 3, 'name' => 'Arnold'),
                            'p' => array('id' => 3, 'phonenumber' => '333 333', 'user_id' => 3)
                            )
                        )
                    );
        $h->setAliasMap(array('u' => array('table' => $this->conn->getTable('User'))));
    }
}
class Doctrine_Hydrate_Mock extends Doctrine_Hydrate2
{
    protected $data;

    public function setData($data)
    {
        $this->data = $data;
    }

    public function _fetch($params = array(), $return = Doctrine::FETCH_RECORD)
    {
        return $this->data;
    }
}
