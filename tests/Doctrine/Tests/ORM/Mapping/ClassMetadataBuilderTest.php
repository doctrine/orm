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
        $this->cm->initializeReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);
        $this->builder = new ClassMetadataBuilder($this->cm);
    }

    public function testSetMappedSuperClass()
    {
        $this->assertIsFluent($this->builder->setMappedSuperClass());
        $this->assertTrue($this->cm->isMappedSuperclass);
    }

    public function testSetCustomRepositoryClass()
    {
        $this->assertIsFluent($this->builder->setCustomRepositoryClass('Doctrine\Tests\Models\CMS\CmsGroup'));
        $this->assertEquals('Doctrine\Tests\Models\CMS\CmsGroup', $this->cm->customRepositoryClassName);
    }

    public function testSetReadOnly()
    {
        $this->assertIsFluent($this->builder->setReadOnly());
        $this->assertTrue($this->cm->isReadOnly);
    }

    public function testSetTable()
    {
        $this->assertIsFluent($this->builder->setTable('users'));
        $this->assertEquals('users', $this->cm->table['name']);
    }

    public function testAddIndex()
    {
        $this->assertIsFluent($this->builder->addIndex(array('username', 'name'), 'users_idx'));
        $this->assertEquals(array('users_idx' => array('columns' => array('username', 'name'))), $this->cm->table['indexes']);
    }

    public function testAddUniqueConstraint()
    {
        $this->assertIsFluent($this->builder->addUniqueConstraint(array('username', 'name'), 'users_idx'));
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

    public function testSetInheritanceJoined()
    {
        $this->assertIsFluent($this->builder->setJoinedTableInheritance());
        $this->assertEquals(ClassMetadata::INHERITANCE_TYPE_JOINED, $this->cm->inheritanceType);
    }

    public function testSetInheritanceSingleTable()
    {
        $this->assertIsFluent($this->builder->setSingleTableInheritance());
        $this->assertEquals(ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE, $this->cm->inheritanceType);
    }

    public function testSetDiscriminatorColumn()
    {
        $this->assertIsFluent($this->builder->setDiscriminatorColumn('discr', 'string', '124'));
        $this->assertEquals(array('fieldName' => 'discr', 'name' => 'discr', 'type' => 'string', 'length' => '124'), $this->cm->discriminatorColumn);
    }

    public function testAddDiscriminatorMapClass()
    {
        $this->assertIsFluent($this->builder->addDiscriminatorMapClass('test', 'Doctrine\Tests\Models\CMS\CmsUser'));
        $this->assertIsFluent($this->builder->addDiscriminatorMapClass('test2', 'Doctrine\Tests\Models\CMS\CmsGroup'));

        $this->assertEquals(array('test' => 'Doctrine\Tests\Models\CMS\CmsUser', 'test2' => 'Doctrine\Tests\Models\CMS\CmsGroup'), $this->cm->discriminatorMap);
        $this->assertEquals('test', $this->cm->discriminatorValue);
    }

    public function testChangeTrackingPolicyExplicit()
    {
        $this->assertIsFluent($this->builder->setChangeTrackingPolicyDeferredExplicit());
        $this->assertEquals(ClassMetadata::CHANGETRACKING_DEFERRED_EXPLICIT, $this->cm->changeTrackingPolicy);
    }

    public function testChangeTrackingPolicyNotify()
    {
        $this->assertIsFluent($this->builder->setChangeTrackingPolicyNotify());
        $this->assertEquals(ClassMetadata::CHANGETRACKING_NOTIFY, $this->cm->changeTrackingPolicy);
    }

    public function testAddField()
    {
        $this->assertIsFluent($this->builder->addField('name', 'string'));
        $this->assertEquals(array('columnName' => 'name', 'fieldName' => 'name', 'type' => 'string'), $this->cm->fieldMappings['name']);
    }

    public function testCreateField()
    {
        $fieldBuilder = ($this->builder->createField('name', 'string'));
        $this->assertInstanceOf('Doctrine\ORM\Mapping\Builder\FieldBuilder', $fieldBuilder);

        $this->assertFalse(isset($this->cm->fieldMappings['name']));
        $this->assertIsFluent($fieldBuilder->build());
        $this->assertEquals(array('columnName' => 'name', 'fieldName' => 'name', 'type' => 'string'), $this->cm->fieldMappings['name']);
    }

    public function testCreateVersionedField()
    {
        $this->builder->createField('name', 'integer')->columnName('username')->length(124)->nullable()->columnDefinition('foobar')->unique()->isVersionField()->build();
        $this->assertEquals(array(
            'columnDefinition' => 'foobar',
            'columnName' => 'username',
            'default' => 1,
            'fieldName' => 'name',
            'length' => 124,
            'type' => 'integer',
            'nullable' => true,
            'unique' => true,
        ), $this->cm->fieldMappings['name']);
    }

    public function testCreatePrimaryField()
    {
        $this->builder->createField('id', 'integer')->isPrimaryKey()->generatedValue()->build();

        $this->assertEquals(array('id'), $this->cm->identifier);
        $this->assertEquals(array('columnName' => 'id', 'fieldName' => 'id', 'id' => true, 'type' => 'integer'), $this->cm->fieldMappings['id']);
    }

    public function testAddLifecycleEvent()
    {
        $this->builder->addLifecycleEvent('getStatus', 'postLoad');

        $this->assertEquals(array('postLoad' => array('getStatus')), $this->cm->lifecycleCallbacks);
    }

    public function testCreateManyToOne()
    {
        $this->assertIsFluent(
            $this->builder->createManyToOne('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                              ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                              ->cascadeAll()
                              ->fetchExtraLazy()
                              ->build()
        );

        $this->assertEquals(array('groups' => array (
                'fieldName' => 'groups',
                'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                'cascade' => array (
                  0 => 'remove',
                  1 => 'persist',
                  2 => 'refresh',
                  3 => 'merge',
                  4 => 'detach',
                ),
                'fetch' => 4,
                'joinColumns' => array (
                  0 =>
                  array (
                    'name' => 'group_id',
                    'referencedColumnName' => 'id',
                    'nullable' => true,
                    'unique' => false,
                    'onDelete' => 'CASCADE',
                    'columnDefinition' => NULL,
                  ),
                ),
                'type' => 2,
                'mappedBy' => NULL,
                'inversedBy' => NULL,
                'isOwningSide' => true,
                'sourceEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
                'isCascadeRemove' => true,
                'isCascadePersist' => true,
                'isCascadeRefresh' => true,
                'isCascadeMerge' => true,
                'isCascadeDetach' => true,
                'sourceToTargetKeyColumns' =>
                array (
                  'group_id' => 'id',
                ),
                'joinColumnFieldNames' =>
                array (
                  'group_id' => 'group_id',
                ),
                'targetToSourceKeyColumns' =>
                array (
                  'id' => 'group_id',
                ),
                'orphanRemoval' => false,
              ),
            ), $this->cm->associationMappings);
    }

    public function testCreateOneToOne()
    {
        $this->assertIsFluent(
            $this->builder->createOneToOne('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                              ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                              ->cascadeAll()
                              ->fetchExtraLazy()
                              ->build()
        );

        $this->assertEquals(array('groups' => array (
                'fieldName' => 'groups',
                'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                'cascade' => array (
                  0 => 'remove',
                  1 => 'persist',
                  2 => 'refresh',
                  3 => 'merge',
                  4 => 'detach',
                ),
                'fetch' => 4,
                'joinColumns' => array (
                  0 =>
                  array (
                    'name' => 'group_id',
                    'referencedColumnName' => 'id',
                    'nullable' => true,
                    'unique' => true,
                    'onDelete' => 'CASCADE',
                    'columnDefinition' => NULL,
                  ),
                ),
                'type' => 1,
                'mappedBy' => NULL,
                'inversedBy' => NULL,
                'isOwningSide' => true,
                'sourceEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
                'isCascadeRemove' => true,
                'isCascadePersist' => true,
                'isCascadeRefresh' => true,
                'isCascadeMerge' => true,
                'isCascadeDetach' => true,
                'sourceToTargetKeyColumns' =>
                array (
                  'group_id' => 'id',
                ),
                'joinColumnFieldNames' =>
                array (
                  'group_id' => 'group_id',
                ),
                'targetToSourceKeyColumns' =>
                array (
                  'id' => 'group_id',
                ),
                'orphanRemoval' => false,
              ),
            ), $this->cm->associationMappings);
    }

    public function testCreateManyToMany()
    {
        $this->assertIsFluent(
            $this->builder->createManyToMany('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                              ->setJoinTable('groups_users')
                              ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                              ->addInverseJoinColumn('user_id', 'id')
                              ->cascadeAll()
                              ->fetchExtraLazy()
                              ->build()
        );

        $this->assertEquals(array(
            'groups' =>
            array(
                'fieldName' => 'groups',
                'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                'cascade' =>
                array(
                    0 => 'remove',
                    1 => 'persist',
                    2 => 'refresh',
                    3 => 'merge',
                    4 => 'detach',
                ),
                'fetch' => 4,
                'joinTable' =>
                array(
                    'joinColumns' =>
                    array(
                        0 =>
                        array(
                            'name' => 'group_id',
                            'referencedColumnName' => 'id',
                            'nullable' => true,
                            'unique' => false,
                            'onDelete' => 'CASCADE',
                            'columnDefinition' => NULL,
                        ),
                    ),
                    'inverseJoinColumns' =>
                    array(
                        0 =>
                        array(
                            'name' => 'user_id',
                            'referencedColumnName' => 'id',
                            'nullable' => true,
                            'unique' => false,
                            'onDelete' => NULL,
                            'columnDefinition' => NULL,
                        ),
                    ),
                    'name' => 'groups_users',
                ),
                'type' => 8,
                'mappedBy' => NULL,
                'inversedBy' => NULL,
                'isOwningSide' => true,
                'sourceEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
                'isCascadeRemove' => true,
                'isCascadePersist' => true,
                'isCascadeRefresh' => true,
                'isCascadeMerge' => true,
                'isCascadeDetach' => true,
                'isOnDeleteCascade' => true,
                'relationToSourceKeyColumns' =>
                array(
                    'group_id' => 'id',
                ),
                'joinTableColumns' =>
                array(
                    0 => 'group_id',
                    1 => 'user_id',
                ),
                'relationToTargetKeyColumns' =>
                array(
                    'user_id' => 'id',
                ),
                'orphanRemoval' => false,
            ),
                ), $this->cm->associationMappings);
    }

    public function testCreateOneToMany()
    {
        $this->assertIsFluent(
                $this->builder->createOneToMany('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                        ->mappedBy('test')
                        ->setOrderBy(array('test'))
                        ->setIndexBy('test')
                        ->build()
        );

        $this->assertEquals(array(
            'groups' =>
            array(
                'fieldName' => 'groups',
                'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                'mappedBy' => 'test',
                'orderBy' =>
                array(
                    0 => 'test',
                ),
                'indexBy' => 'test',
                'type' => 4,
                'inversedBy' => NULL,
                'isOwningSide' => false,
                'sourceEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
                'fetch' => 2,
                'cascade' =>
                array(
                ),
                'isCascadeRemove' => false,
                'isCascadePersist' => false,
                'isCascadeRefresh' => false,
                'isCascadeMerge' => false,
                'isCascadeDetach' => false,
                'orphanRemoval' => false,
            ),
                ), $this->cm->associationMappings);
    }

    public function assertIsFluent($ret)
    {
        $this->assertSame($this->builder, $ret, "Return Value has to be same instance as used builder");
    }
}
