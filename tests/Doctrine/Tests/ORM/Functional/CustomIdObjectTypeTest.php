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

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\DbalTypes\CustomIdObject;
use Doctrine\Tests\DbalTypes\CustomIdObjectType;
use Doctrine\Tests\Models\CustomType\CustomIdObjectTypeChild;
use Doctrine\Tests\Models\CustomType\CustomIdObjectTypeParent;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\DBAL\Types\Type as DBALType;

class CustomIdObjectTypeTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        if (DBALType::hasType(CustomIdObjectType::NAME)) {
            DBALType::overrideType(CustomIdObjectType::NAME, CustomIdObjectType::CLASSNAME);
        } else {
            DBALType::addType(CustomIdObjectType::NAME, CustomIdObjectType::CLASSNAME);
        }

        $this->useModelSet('custom_id_object_type');

        parent::setUp();
    }

    public function testFindByCustomIdObject()
    {
        $parent = new CustomIdObjectTypeParent(new CustomIdObject('foo'));

        $this->_em->persist($parent);
        $this->_em->flush();

        $result = $this->_em->find(CustomIdObjectTypeParent::CLASSNAME, $parent->id);

        $this->assertSame($parent, $result);
    }

    /**
     * @group DDC-3622
     * @group 1336
     */
    public function testFetchJoinCustomIdObject()
    {
        $parent = new CustomIdObjectTypeParent(new CustomIdObject('foo'));

        $parent->children->add(new CustomIdObjectTypeChild(new CustomIdObject('bar'), $parent));

        $this->_em->persist($parent);
        $this->_em->flush();

        $result = $this
            ->_em
            ->createQuery(
                'SELECT parent, children FROM '
                . CustomIdObjectTypeParent::CLASSNAME
                . ' parent LEFT JOIN parent.children children'
            )
            ->getResult();

        $this->assertCount(1, $result);
        $this->assertSame($parent, $result[0]);
    }

    /**
     * @group DDC-3622
     * @group 1336
     */
    public function testFetchJoinWhereCustomIdObject()
    {
        $parent = new CustomIdObjectTypeParent(new CustomIdObject('foo'));

        $parent->children->add(new CustomIdObjectTypeChild(new CustomIdObject('bar'), $parent));

        $this->_em->persist($parent);
        $this->_em->flush();

        // note: hydration is willingly broken in this example:
        $result = $this
            ->_em
            ->createQuery(
                'SELECT parent, children FROM '
                . CustomIdObjectTypeParent::CLASSNAME
                . ' parent LEFT JOIN parent.children children '
                . 'WHERE children.id = ?1'
            )
            ->setParameter(1, $parent->children->first()->id)
            ->getResult();

        $this->assertCount(1, $result);
        $this->assertSame($parent, $result[0]);
    }
}
