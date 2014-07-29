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

namespace Doctrine\Tests\ORM\Query\Filter;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\ORM\Query\Filter\DefaultFilterFactory;

/**
 * @coversDefaultClass Doctrine\ORM\Query\Filter\DefaultFilterFactory
 */
class DefaultFilterFactoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \Doctrine\ORM\Query\Filter\DefaultFilterFactory
     */
    private $filterFactory;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->filterFactory = new DefaultFilterFactory();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $config = new Configuration();
        $config->addFilter('existingFilter', 'Doctrine\Tests\ORM\Query\Filter\MockFilter');
        $this->em->expects($this->any())->method('getConfiguration')->will($this->returnValue($config));
    }

    /**
     * @covers ::canCreate
     */
    public function testCanCreate()
    {
        $this->assertTrue($this->filterFactory->canCreate($this->em, 'existingFilter'));
        $this->assertFalse($this->filterFactory->canCreate($this->em, 'wrongFilter'));
    }

    /**
     * @covers ::createFilter
     */
    public function testCreateFilter()
    {
        $filter = $this->filterFactory->createFilter($this->em, 'existingFilter');
        $this->assertInstanceOf('Doctrine\Tests\ORM\Query\Filter\MockFilter', $filter);
    }

    /**
     * @covers ::createFilter
     * @expectedException Doctrine\ORM\Query\Filter\FilterNotFoundException
     */
    public function testCreateFilterWithWrongFilter()
    {
        $this->filterFactory->createFilter($this->em, 'wrongFilter');
    }
}

class MockFilter extends SQLFilter
{

    public function addFilterConstraint(
        ClassMetadata $targetEntity,
        $targetTableAlias
    ) {
    }
}

