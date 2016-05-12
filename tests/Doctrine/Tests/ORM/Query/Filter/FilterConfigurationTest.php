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
use Doctrine\ORM\Query\Filter\FiltersConfiguration;
use Doctrine\ORM\Query\Filter\FiltersConfigurationInterface;
use Doctrine\Tests\Models\Filter\DummyFilterFactory;
use PHPUnit_Framework_TestCase;

class FilterConfigurationTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var FiltersConfigurationInterface
     */
    private $filterConfiguration;

    protected function setUp()
    {
        $ormConfigurationMock = $this->prophesize('Doctrine\ORM\Configuration');
        $this->filterConfiguration = new FiltersConfiguration($ormConfigurationMock->reveal());
    }

    public function testGetFilterFactory()
    {
        $defaultFilterFactory = $this->filterConfiguration->getFilterFactory();
        $this->assertInstanceOf('Doctrine\ORM\Query\Filter\FilterFactoryInterface', $defaultFilterFactory);
        $this->assertInstanceOf('Doctrine\ORM\Query\Filter\DefaultFilterFactory', $defaultFilterFactory);
    }

    public function testSetFilterFactory()
    {
        $dummyFilterFactory = new DummyFilterFactory;
        $this->filterConfiguration->setFilterFactory($dummyFilterFactory);

        $filterFactory = $this->filterConfiguration->getFilterFactory();
        $this->assertInstanceOf('Doctrine\ORM\Query\Filter\FilterFactoryInterface', $filterFactory);
        $this->assertNotInstanceOf('Doctrine\ORM\Query\Filter\DefaultFilterFactory', $filterFactory);
        $this->assertInstanceOf('Doctrine\Tests\Models\Filter\DummyFilterFactory', $filterFactory);
    }
}
