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
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Tests\DBAL\Schema;

require_once __DIR__ . '/../../TestInit.php';

use Doctrine\DBAL\Schema\Schema,
    Doctrine\DBAL\Schema\Table,
    Doctrine\DBAL\Schema\Column,
    Doctrine\DBAL\Schema\Index,
    Doctrine\DBAL\Schema\Sequence,
    Doctrine\DBAL\Schema\SchemaDiff,
    Doctrine\DBAL\Schema\TableDiff,
    Doctrine\DBAL\Schema\Comparator,
    Doctrine\DBAL\Types\Type;

/**
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @since   2.0
 * @version $Revision$
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 */
class ComparatorTest extends \PHPUnit_Framework_TestCase
{
    public function testCompareSame1()
    {
        $schema1 = new Schema( array(
            'bugdb' => new Table('bugdb',
                array (
                    'integerfield1' => new Column('integerfield1', Type::getType('integer' ) ),
                )
            ),
        ) );
        $schema2 = new Schema( array(
            'bugdb' => new Table('bugdb',
                array (
                    'integerfield1' => new Column('integerfield1', Type::getType('integer') ),
                )
            ),
        ) );

        $this->assertEquals(new SchemaDiff(), Comparator::compareSchemas( $schema1, $schema2 ) );
    }

    public function testCompareSame2()
    {
        $schema1 = new Schema( array(
            'bugdb' => new Table('bugdb',
                array (
                    'integerfield1' => new Column('integerfield1', Type::getType('integer')),
                    'integerfield2' => new Column('integerfield2', Type::getType('integer')),
                )
            ),
        ) );
        $schema2 = new Schema( array(
            'bugdb' => new Table('bugdb',
                array (
                    'integerfield2' => new Column('integerfield2', Type::getType('integer')),
                    'integerfield1' => new Column('integerfield1', Type::getType('integer')),
                )
            ),
        ) );
        $this->assertEquals(new SchemaDiff(), Comparator::compareSchemas( $schema1, $schema2 ) );
    }

    public function testCompareMissingTable()
    {
        $schema1 = new Schema( array(
            'bugdb' => new Table('bugdb',
                array (
                    'integerfield1' => new Column('integerfield1', Type::getType('integer')),
                )
            ),
        ) );
        $schema2 = new Schema( array(
        ) );

        $expected = new SchemaDiff( array(), array(),
            array(
                'bugdb' => new Table('bugdb',
                array (
                    'integerfield1' => new Column('integerfield1', Type::getType('integer')),
                )
            ),
            )
        );
        $this->assertEquals($expected, Comparator::compareSchemas( $schema1, $schema2 ) );
    }

    public function testCompareNewTable()
    {
        $schema1 = new Schema( array(
        ) );
        $schema2 = new Schema( array(
            'bugdb' => new Table('bugdb',
                array (
                    'integerfield1' => new Column('integerfield1', Type::getType('integer')),
                )
            ),
        ) );

        $expected = new SchemaDiff( array(
            'bugdb' => new Table('bugdb',
                array (
                    'integerfield1' => new Column('integerfield1', Type::getType('integer')),
                )
            ),
        ) );
        $this->assertEquals($expected, Comparator::compareSchemas( $schema1, $schema2 ) );
    }

    public function testCompareMissingField()
    {
        $schema1 = new Schema( array(
            'bugdb' => new Table('bugdb',
                array (
                    'integerfield1' => new Column('integerfield1', Type::getType('integer')),
                    'integerfield2' => new Column('integerfield2', Type::getType('integer')),
                )
            ),
        ) );
        $schema2 = new Schema( array(
            'bugdb' => new Table('bugdb',
                array (
                    'integerfield2' => new Column('integerfield2', Type::getType('integer')),
                )
            ),
        ) );

        $expected = new SchemaDiff ( array(),
            array (
                'bugdb' => new TableDiff( array(), array(),
                    array (
                        'integerfield1' => true,
                    )
                )
            )
        );
        $this->assertEquals($expected, Comparator::compareSchemas( $schema1, $schema2 ) );
    }

    public function testCompareNewField()
    {
        $schema1 = new Schema( array(
            'bugdb' => new Table('bugdb',
                array (
                    'integerfield1' => new Column('integerfield1', Type::getType('integer')),
                )
            ),
        ) );
        $schema2 = new Schema( array(
            'bugdb' => new Table('bugdb',
                array (
                    'integerfield1' => new Column('integerfield1', Type::getType('integer')),
                    'integerfield2' => new Column('integerfield2', Type::getType('integer')),
                )
            ),
        ) );

        $expected = new SchemaDiff ( array(),
            array (
                'bugdb' => new TableDiff (
                    array (
                        'integerfield2' => new Column('integerfield2', Type::getType('integer')),
                    )
                ),
            )
        );
        $this->assertEquals($expected, Comparator::compareSchemas( $schema1, $schema2 ) );
    }

    public function testCompareChangedColumns_ChangeType()
    {
        $schema1 = new Schema( array(
            'bugdb' => new Table('bugdb',
                array (
                    'charfield1' => new Column('charfield1', Type::getType('string')),
                )
            ),
        ) );
        $schema2 = new Schema( array(
            'bugdb' => new Table('bugdb',
                array (
                    'charfield1' => new Column('charfield1', Type::getType('integer')),
                )
            ),
        ) );

        $expected = new SchemaDiff ( array(),
            array (
                'bugdb' => new TableDiff( array(),
                    array (
                        'charfield1' => new Column('charfield1', Type::getType('integer')),
                    )
                ),
            )
        );
        $this->assertEquals($expected, Comparator::compareSchemas( $schema1, $schema2 ) );
    }

    public function testCompareRemovedIndex()
    {
        $schema1 = new Schema( array(
            'bugdb' => new Table('bugdb',
                array (
                    'integerfield1' => new Column('integerfield1', Type::getType('integer')),
                    'integerfield2' => new Column('integerfield2', Type::getType('integer')),
                ),
                array (
                    'primary' => new Index('primary',
                        array(
                            'integerfield1'
                        ),
                        true
                    )
                )
            ),
        ) );
        $schema2 = new Schema( array(
            'bugdb' => new Table('bugdb',
                array (
                    'integerfield1' => new Column('integerfield1', Type::getType('integer')),
                    'integerfield2' => new Column('integerfield2', Type::getType('integer')),
                )
            ),
        ) );

        $expected = new SchemaDiff ( array(),
            array (
                'bugdb' => new TableDiff( array(), array(), array(), array(), array(),
                    array (
                        'primary' => true
                    )
                ),
            )
        );
        $this->assertEquals($expected, Comparator::compareSchemas( $schema1, $schema2 ) );
    }

    public function testCompareNewIndex()
    {
        $schema1 = new Schema( array(
            'bugdb' => new Table('bugdb',
                array (
                    'integerfield1' => new Column('integerfield1', Type::getType('integer')),
                    'integerfield2' => new Column('integerfield2', Type::getType('integer')),
                )
            ),
        ) );
        $schema2 = new Schema( array(
            'bugdb' => new Table('bugdb',
                array (
                    'integerfield1' => new Column('integerfield1', Type::getType('integer')),
                    'integerfield2' => new Column('integerfield2', Type::getType('integer')),
                ),
                array (
                    'primary' => new Index('primary',
                        array(
                            'integerfield1'
                        ),
                        true
                    )
                )
            ),
        ) );

        $expected = new SchemaDiff ( array(),
            array (
                'bugdb' => new TableDiff( array(), array(), array(),
                    array (
                        'primary' => new Index('primary',
                            array(
                                'integerfield1'
                            ),
                            true
                        )
                    )
                ),
            )
        );
        $this->assertEquals($expected, Comparator::compareSchemas( $schema1, $schema2 ) );
    }

    public function testCompareChangedIndex()
    {
        $schema1 = new Schema( array(
            'bugdb' => new Table('bugdb',
                array (
                    'integerfield1' => new Column('integerfield1', Type::getType('integer')),
                    'integerfield2' => new Column('integerfield2', Type::getType('integer')),
                ),
                array (
                    'primary' => new Index('primary',
                        array(
                            'integerfield1'
                        ),
                        true
                    )
                )
            ),
        ) );
        $schema2 = new Schema( array(
            'bugdb' => new Table('bugdb',
                array (
                    'integerfield1' => new Column('integerfield1', Type::getType('integer')),
                    'integerfield2' => new Column('integerfield2', Type::getType('integer')),
                ),
                array (
                    'primary' => new Index('primary',
                        array('integerfield1', 'integerfield2'),
                        true
                    )
                )
            ),
        ) );

        $expected = new SchemaDiff ( array(),
            array (
                'bugdb' => new TableDiff( array(), array(), array(), array(),
                    array (
                        'primary' => new Index('primary',
                            array(
                                'integerfield1',
                                'integerfield2'
                            ),
                            true
                        )
                    )
                ),
            )
        );
        $actual = Comparator::compareSchemas( $schema1, $schema2 );
        $this->assertEquals($expected, $actual);
    }
}