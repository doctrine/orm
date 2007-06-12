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
 * Doctrine_Ticket343_TestCase
 *
 * @package     Doctrine
 * @author      Lloyd Leung (lleung at carlton decimal ca)
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Ticket343_TestCase extends Doctrine_UnitTestCase
{
    public function prepareData() 
    { }
    public function prepareTables()
    { }
    public function testForeignPkNonId()
    {
        $member = new Member();
        $subprogram = new SubProgram();
        $newsblast = new NewsBlast();

        $member->set('name','hello world!');
        $member->set('pin', 'demo1100');

        $subprogram->set('name', 'SoemthingNew');

        $newsblast->set('member', $member);
        $newsblast->set('subprogram', $subprogram);
        $newsblast->set('title', 'Some title here');

        $newsblast->save();

        $test->assertEqual($newsblast['subprogram'], 'SomethingNew');
        $test->assertEqual($newsblast['member']['pin'], 'demo1100');
        $test->assertEqual($newsblast['member']['name'], 'hello world!');
    }
}


class Member extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('members');

        $this->hasColumn('pin', 'string', 8, array('primary' => true));
        $this->hasColumn('name', 'string', 254, array('notblank' => true));
    }

    public function setUp()
    {
        $this->hasMany('NewsBlast as news_blasts', 'NewsBlast.pin');
    }
}


class NewsBlast extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('p2m_newsblast');
        $this->hasColumn('pin', 'string', 8, array('primary' => true));
        $this->hasColumn('subprogram_id', 'integer', 10, array());
        $this->hasColumn('title', 'string', 254, array());
    }

    public function setUp()
    {
        $this->hasOne('SubProgram as subprogram', 'NewsBlast.subprogram_id');
        $this->hasOne('Member as member', 'NewsBlast.pin');
    }
}
class SubProgram extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('p2m_subprogram');
        $this->hasColumn('name', 'string', 50, array());
    }

    public function setUp()
    {
        $this->hasMany('Member as members', 'Member.subprogram_id');
    }
}

