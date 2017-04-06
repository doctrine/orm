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

namespace Doctrine\Tests\ORM\Mapping\Builder;

use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\Builder\DiscriminatorColumnMetadataBuilder;
use Doctrine\ORM\Mapping\Builder\TableMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\Tests\OrmTestCase;

/**
 * @group DDC-659
 */
class TableMetadataBuilderTest extends OrmTestCase
{
    /**
     * @var TableMetadataBuilder
     */
    private $builder;

    public function setUp()
    {
        $this->builder = new TableMetadataBuilder();
    }

    public function testWithSchema()
    {
        self::assertIsFluent($this->builder->withSchema('public'));

        $tableMetadata = $this->builder->build();

        self::assertEquals('public', $tableMetadata->getSchema());
    }


    public function testWithName()
    {
        self::assertIsFluent($this->builder->withName('users'));

        $tableMetadata = $this->builder->build();

        self::assertEquals('users', $tableMetadata->getName());
    }

    public function testWithIndex()
    {
        self::assertIsFluent($this->builder->withIndex('users_idx', ['username', 'name']));

        $tableMetadata = $this->builder->build();

        self::assertEquals(
            [
                'users_idx' => [
                    'name'    => 'users_idx',
                    'columns' => ['username', 'name'],
                    'unique'  => false,
                    'options' => [],
                    'flags'   => [],
                ]
            ],
            $tableMetadata->getIndexes()
        );
    }

    public function testAddUniqueConstraint()
    {
        self::assertIsFluent($this->builder->withUniqueConstraint('users_idx', ['username', 'name']));

        $tableMetadata = $this->builder->build();

        self::assertEquals(
            [
                'users_idx' => [
                    'name'    => 'users_idx',
                    'columns' => ['username', 'name'],
                    'options' => [],
                    'flags'   => [],
                ]
            ],
            $tableMetadata->getUniqueConstraints()
        );
    }

    public function testSetTableRelated()
    {
        $this->builder->withUniqueConstraint('users_idx', ['username', 'name']);
        $this->builder->withIndex('users_idx', ['username', 'name']);
        $this->builder->withSchema('public');
        $this->builder->withName('users');

        $tableMetadata = $this->builder->build();

        self::assertEquals('public', $tableMetadata->getSchema());
        self::assertEquals('users', $tableMetadata->getName());
        self::assertEquals(
            [
                'users_idx' => [
                    'name'    => 'users_idx',
                    'columns' => ['username', 'name'],
                    'unique'  => false,
                    'options' => [],
                    'flags'   => [],
                ]
            ],
            $tableMetadata->getIndexes()
        );
        self::assertEquals(
            [
                'users_idx' => [
                    'name'    => 'users_idx',
                    'columns' => ['username', 'name'],
                    'options' => [],
                    'flags'   => [],
                ]
            ],
            $tableMetadata->getUniqueConstraints()
        );
    }

    protected function assertIsFluent($ret)
    {
        self::assertSame($this->builder, $ret, "Return Value has to be same instance as used builder");
    }
}
