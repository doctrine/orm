<?php
/*
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


namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

/**
 * @group DDC-659
 */
class ClassMetadataBuilderTest extends \Doctrine\Tests\OrmTestCase
{
    /**
     * @var ClassMetadata
     */
    private $cm;
    /**
     * @var ClassMetadataBuilder
     */
    private $builder;

    public function setUp()
    {
        $this->cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $this->builder = new ClassMetadataBuilder($this->cm);
    }

    public function testSetMappedSuperClass()
    {
        $this->builder->setMappedSuperClass();

        $this->assertTrue($this->cm->isMappedSuperclass);
    }

    public function testSetCustomRepositoryClass()
    {
        $this->builder->setCustomRepositoryClass('Doctrine\Tests\Models\CMS\CmsGroup');

        $this->assertEquals('Doctrine\Tests\Models\CMS\CmsGroup', $this->cm->customRepositoryClassName);
    }

    public function testSetReadOnly()
    {
        $this->builder->setReadOnly();
        $this->assertTrue($this->cm->isReadOnly);
    }

    public function testSetTable()
    {
        $this->builder->setTable('users');
        $this->assertEquals('users', $this->cm->table['name']);
    }

    public function testAddIndex()
    {
        $this->builder->addIndex(array('username', 'name'), 'users_idx');
        $this->assertEquals(array('users_idx' => array('columns' => array('username', 'name'))), $this->cm->table['indexes']);
    }

    public function testAddUniqueConstraint()
    {
        $this->builder->addUniqueConstraint(array('username', 'name'), 'users_idx');
        $this->assertEquals(array('users_idx' => array('columns' => array('username', 'name'))), $this->cm->table['uniqueConstraints']);
    }

    public function testSetPrimaryTableRelated()
    {
        $this->builder->addUniqueConstraint(array('username', 'name'), 'users_idx');
        $this->builder->addIndex(array('username', 'name'), 'users_idx');
        $this->builder->setTable('users');

        $this->assertEquals(
            array(
                'name' => 'users',
                'indexes' => array('users_idx' => array('columns' => array('username', 'name'))),
                'uniqueConstraints' => array('users_idx' => array('columns' => array('username', 'name'))),
            ),
            $this->cm->table
        );
    }
}