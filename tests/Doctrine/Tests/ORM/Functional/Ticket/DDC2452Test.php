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
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Tests\ORM\Functional\Ticket;

/**
 * @group DDC-2452
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 */
class DDC2452Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function testTicket()
    {
        if ($this->_em->getConnection()->getDatabasePlatform()->getName() != 'sqlite') {
            // @todo set the abstract platform?
            $this->markTestSkipped("This test is useful for all databases, but designed only for mysql.");
        }

        $dql = 'SELECT foo1 FROM '
            . __NAMESPACE__ . '\DDC2452Foo foo1'
            . ' LEFT JOIN '
            . __NAMESPACE__ . '\DDC2452Foo foo2'
            . ' WITH 1 = 1';

        $sql = $this->_em->createQuery($dql)->getSQL();

        $this->assertStringMatchesFormat(
            'SELECT %s FROM %s LEFT JOIN %s ON %s LEFT JOIN %s LEFT JOIN %s ON %s = %s AND (1 = 1)',
            $sql,
            'The generated SQL adds conditions defined in `WITH` to the existing SQL joins produced by the inheritance'
        );
    }
}

/**
 * @Entity
 * @Table(name="foo")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"foo" = "DDC2452Foo", "bar" = "DDC2452Bar"})
 */
class DDC2452Foo
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
}

/**
 * @Entity
 * @Table(name="bar")
 */
class DDC2452Bar extends DDC2452Foo
{
}
