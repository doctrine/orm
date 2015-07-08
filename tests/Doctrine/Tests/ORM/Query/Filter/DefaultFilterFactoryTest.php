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

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Filter\DefaultFilterFactory;
use Doctrine\ORM\Query\Filter\FilterFactoryInterface;
use Doctrine\Tests\OrmTestCase;


const DUMMY_FILTER_CLASS = 'Doctrine\Tests\Models\Filter\DummyFilter';


class DefaultFilterFactoryTest extends OrmTestCase
{
    /**
     * @var FilterFactoryInterface
     */
    private $defaultFilterFactory;

    protected function setUp()
    {
        $ormConfigurationMock = $this->prophesize('Doctrine\ORM\Configuration');
        $ormConfigurationMock->getFilterClassName('someFilter')->will(function () {
            return DUMMY_FILTER_CLASS;
        });
        $this->defaultFilterFactory = new DefaultFilterFactory($ormConfigurationMock->reveal());
    }

    public function testCreateFromName()
    {
        $filter = $this->defaultFilterFactory->createFromName('someFilter');
        $this->assertInstanceOf(DUMMY_FILTER_CLASS, $filter);
    }
}
